<?php

namespace App\Services;

use App\Events\BookingCancelled;
use App\Events\BookingCompleted;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Events\BookingPickedUp;
use App\Events\BookingRejected;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingService
{
    private const MAX_REFERENCE_RETRIES = 5;

    /**
     * Create a new booking with full business validation.
     *
     * Workflow:
     * 1. Validate customer
     * 2. Validate vehicle
     * 3. Validate dates
     * 4. Check for overlapping bookings
     * 5. Calculate pricing
     * 6. Generate unique reference
     * 7. Persist within transaction
     * 8. Fire domain event
     */
    public function createBooking(array $data, int $userId): Booking
    {
        $user = $this->findUserOrFail($userId);
        $this->validateCustomer($user);

        $vehicle = $this->findVehicleOrFail($data['vehicle_id']);
        $this->validateVehicle($vehicle);

        // Ensure vehicle belongs to requested branch when provided
        if (!empty($data['branch_id']) && (int) $data['branch_id'] !== (int) $vehicle->branch_id) {
            throw new \InvalidArgumentException('The selected vehicle does not belong to the chosen branch.');
        }

        $pickupDate = Carbon::parse($data['pickup_date']);
        $returnDate = Carbon::parse($data['return_date']);
        $this->validateDates($pickupDate, $returnDate);
        $this->validateNoOverlap($vehicle->id, $pickupDate, $returnDate);

        $numberOfDays = $this->calculateNumberOfDays($pickupDate, $returnDate);
        $pricePerDay = $this->getPricePerDay($vehicle);
        $subtotal = $this->calculateSubtotal($numberOfDays, $pricePerDay);
        $additionalCharges = $this->resolveAdditionalCharges($data);
        $discount = $this->resolveDiscount($data);
        $totalPrice = $this->calculateTotalPrice($subtotal, $additionalCharges, $discount);
        $bookingReference = $this->generateUniqueReference();

        $booking = DB::transaction(function () use (
            $bookingReference, $userId, $vehicle, $data,
            $pickupDate, $returnDate, $numberOfDays,
            $pricePerDay, $subtotal, $additionalCharges,
            $discount, $totalPrice
        ) {
            $booking = Booking::create([
                'booking_reference'  => $bookingReference,
                'user_id'            => $userId,
                'vehicle_id'         => $vehicle->id,
                'branch_id'          => $vehicle->branch_id,
                'pickup_location'    => $data['pickup_location'],
                'return_location'    => $data['return_location'],
                'pickup_date'        => $pickupDate,
                'return_date'        => $returnDate,
                'number_of_days'     => $numberOfDays,
                'price_per_day'      => $pricePerDay,
                'subtotal'           => $subtotal,
                'additional_charges' => $additionalCharges,
                'discount'           => $discount,
                'total_price'        => $totalPrice,
                'status'                 => Booking::STATUS_PENDING,
                'payment_status'         => Booking::PAYMENT_STATUS_UNPAID,
                'branch_approval_status' => Booking::APPROVAL_PENDING,
                'admin_approval_status'  => Booking::APPROVAL_PENDING,
                'notes'                  => $data['notes'] ?? null,
            ]);

            $booking->load('vehicle', 'user');

            return $booking;
        });

        event(new BookingCreated($booking));

        Log::info('Booking created successfully', [
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'user_id' => $userId,
            'vehicle_id' => $vehicle->id,
            'total_price' => $totalPrice,
        ]);

        return $booking;
    }

    /**
     * Branch manager approves a pending booking (first approval step).
     */
    public function approveByBranch(Booking $booking, User $approver): Booking
    {
        if (!$approver->isBranchManager() && !$approver->isAdmin()) {
            throw new \InvalidArgumentException('Only branch managers can perform branch approval.');
        }

        if ($approver->isBranchManager() && (int) $approver->branch_id !== (int) $booking->branch_id) {
            throw new \InvalidArgumentException('You can only approve bookings for your own branch.');
        }

        if ($booking->status !== Booking::STATUS_PENDING && $booking->status !== Booking::STATUS_BRANCH_REVIEW) {
            throw new \InvalidArgumentException('Only pending bookings can be branch-approved.');
        }

        if ($booking->branch_approval_status === Booking::APPROVAL_APPROVED) {
            throw new \InvalidArgumentException('Booking is already branch-approved.');
        }

        if ($booking->branch_approval_status === Booking::APPROVAL_REJECTED) {
            throw new \InvalidArgumentException('Booking was rejected by the branch.');
        }

        $booking = DB::transaction(function () use ($booking, $approver) {
            $booking->update([
                'branch_approval_status' => Booking::APPROVAL_APPROVED,
                'branch_approved_at'     => now(),
                'branch_approved_by'     => $approver->id,
                'admin_approval_status'  => Booking::APPROVAL_APPROVED,
                'admin_approved_at'      => now(),
                'admin_approved_by'      => $approver->id,
                'status'                 => Booking::STATUS_CONFIRMED,
                'payment_status'         => Booking::PAYMENT_STATUS_PENDING,
            ]);

            $booking->vehicle->update(['status' => 'reserved']);

            return $booking->fresh()->load('vehicle', 'user', 'branch');
        });

        event(new BookingConfirmed($booking));

        Log::info('Booking branch-approved and confirmed', [
            'booking_id' => $booking->id,
            'approver_id' => $approver->id,
        ]);

        return $booking;
    }

    /**
     * Admin gives final approval — booking becomes confirmed.
     */
    public function approveByAdmin(Booking $booking, User $approver): Booking
    {
        if (!$approver->isAdmin()) {
            throw new \InvalidArgumentException('Only administrators can give final approval.');
        }

        if ($booking->status !== Booking::STATUS_PENDING && $booking->status !== Booking::STATUS_BRANCH_REVIEW) {
            throw new \InvalidArgumentException('Only pending bookings can receive admin approval.');
        }

        if ($booking->admin_approval_status === Booking::APPROVAL_APPROVED) {
            throw new \InvalidArgumentException('Booking is already admin-approved.');
        }

        $booking = DB::transaction(function () use ($booking, $approver) {
            $booking->update([
                'branch_approval_status' => Booking::APPROVAL_APPROVED,
                'branch_approved_at'     => $booking->branch_approved_at ?? now(),
                'branch_approved_by'     => $booking->branch_approved_by ?? $approver->id,
                'admin_approval_status' => Booking::APPROVAL_APPROVED,
                'admin_approved_at'     => now(),
                'admin_approved_by'     => $approver->id,
                'status'                => Booking::STATUS_CONFIRMED,
                'payment_status'        => Booking::PAYMENT_STATUS_PENDING,
            ]);

            $booking->vehicle->update(['status' => 'reserved']);

            return $booking->fresh()->load('vehicle', 'user', 'branch');
        });

        event(new BookingConfirmed($booking));

        Log::info('Booking admin-approved and confirmed', [
            'booking_id' => $booking->id,
            'approver_id' => $approver->id,
        ]);

        return $booking;
    }

    /**
     * Confirm a pending booking (legacy — routes to role-appropriate approval).
     */
    public function confirmBooking(Booking $booking, ?User $actor = null): Booking
    {
        $actor = $actor ?? auth()->user();

        if ($actor->isBranchManager()) {
            return $this->approveByBranch($booking, $actor);
        }

        if ($actor->isAdmin()) {
            return $this->approveByAdmin($booking, $actor);
        }

        return $this->approveByAdmin($booking, $actor);
    }

    /**
     * Reject a booking — branch manager or admin depending on actor role.
     */
    public function rejectBooking(Booking $booking, ?string $reason = null, ?User $actor = null): Booking
    {
        $actor = $actor ?? auth()->user();

        if ($booking->status !== Booking::STATUS_PENDING) {
            throw new \InvalidArgumentException('Only pending bookings can be rejected.');
        }

        if ($actor->isBranchManager()) {
            return $this->rejectByBranch($booking, $actor, $reason);
        }

        return $this->rejectByAdmin($booking, $actor, $reason);
    }

    public function rejectByBranch(Booking $booking, User $rejector, ?string $reason = null): Booking
    {
        if (!$rejector->isBranchManager() && !$rejector->isAdmin()) {
            throw new \InvalidArgumentException('Unauthorized to reject this booking.');
        }

        if ($rejector->isBranchManager() && (int) $rejector->branch_id !== (int) $booking->branch_id) {
            throw new \InvalidArgumentException('You can only reject bookings for your own branch.');
        }

        return $this->performRejection($booking, $rejector, 'branch', $reason);
    }

    public function rejectByAdmin(Booking $booking, User $rejector, ?string $reason = null): Booking
    {
        if (!$rejector->isAdmin()) {
            throw new \InvalidArgumentException('Only administrators can reject at admin level.');
        }

        return $this->performRejection($booking, $rejector, 'admin', $reason);
    }

    private function performRejection(Booking $booking, User $rejector, string $role, ?string $reason): Booking
    {
        $notes = $booking->notes;
        if ($reason) {
            $notes = $notes ? $notes . "\nRejection: " . $reason : 'Rejection: ' . $reason;
        }

        $booking = DB::transaction(function () use ($booking, $rejector, $role, $reason, $notes) {
            $updates = [
                'status'              => Booking::STATUS_REJECTED,
                'rejected_by_role'    => $role,
                'rejected_by_user_id' => $rejector->id,
                'rejected_at'         => now(),
                'rejection_reason'    => $reason,
                'notes'               => $notes,
            ];

            if ($role === 'branch') {
                $updates['branch_approval_status'] = Booking::APPROVAL_REJECTED;
            } else {
                $updates['admin_approval_status'] = Booking::APPROVAL_REJECTED;
            }

            $booking->update($updates);

            if ($booking->vehicle->status === 'reserved') {
                $booking->vehicle->update(['status' => 'available']);
            }

            return $booking->fresh()->load('vehicle', 'user', 'branch');
        });

        event(new BookingRejected($booking, $reason));

        return $booking;
    }

    /**
     * Cancel a pending or confirmed booking.
     */
    public function cancelBooking(Booking $booking): Booking
    {
        if (!in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])) {
            throw new \InvalidArgumentException('Only pending or confirmed bookings can be cancelled.');
        }

        $booking = DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => Booking::STATUS_CANCELLED,
                'payment_status' => Booking::PAYMENT_STATUS_UNPAID,
            ]);

            $booking->payments()
                ->where('status', Payment::STATUS_PENDING)
                ->update(['status' => Payment::STATUS_FAILED]);

            if ($booking->vehicle->status === 'reserved') {
                $booking->vehicle->update(['status' => 'available']);
            }

            return $booking->fresh()->load('vehicle', 'user');
        });

        event(new BookingCancelled($booking));

        Log::info('Booking cancelled', [
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
        ]);

        return $booking;
    }

    /**
     * Mark a confirmed booking as picked up (active rental).
     */
    public function markAsPickedUp(Booking $booking): Booking
    {
        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            throw new \InvalidArgumentException('Only confirmed bookings can be marked as picked up.');
        }

        $booking = DB::transaction(function () use ($booking) {
            $booking->update(['status' => Booking::STATUS_ACTIVE]);
            $booking->vehicle->update(['status' => 'rented']);

            return $booking->fresh()->load('vehicle', 'user');
        });

        event(new BookingPickedUp($booking));

        Log::info('Booking picked up', [
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
        ]);

        return $booking;
    }

    /**
     * Mark an active booking as returned (completed).
     */
    public function markAsReturned(Booking $booking): Booking
    {
        if ($booking->status !== Booking::STATUS_ACTIVE) {
            throw new \InvalidArgumentException('Only active rentals can be returned.');
        }

        $booking = DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => Booking::STATUS_COMPLETED,
                'payment_status' => Booking::PAYMENT_STATUS_PAID,
            ]);

            $booking->vehicle->update(['status' => 'available']);

            return $booking->fresh()->load('vehicle', 'user');
        });

        event(new BookingCompleted($booking));

        Log::info('Booking completed', [
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
        ]);

        return $booking;
    }

    /**
     * Check if a vehicle has overlapping bookings for the given date range.
     */
    public function hasOverlap(int $vehicleId, Carbon $pickupDate, Carbon $returnDate, ?int $excludeBookingId = null): bool
    {
        $query = Booking::overlapping($vehicleId, $pickupDate, $returnDate);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    /**
     * Calculate the rental price breakdown for a vehicle and date range.
     * Returns raw values without persisting.
     */
    public function calculatePriceBreakdown(Vehicle $vehicle, Carbon $pickupDate, Carbon $returnDate, float $additionalCharges = 0, float $discount = 0): array
    {
        $numberOfDays = $this->calculateNumberOfDays($pickupDate, $returnDate);
        $pricePerDay = $this->getPricePerDay($vehicle);
        $subtotal = $this->calculateSubtotal($numberOfDays, $pricePerDay);
        $totalPrice = $this->calculateTotalPrice($subtotal, $additionalCharges, $discount);

        return [
            'vehicle_id' => $vehicle->id,
            'price_per_day' => $pricePerDay,
            'number_of_days' => $numberOfDays,
            'subtotal' => $subtotal,
            'additional_charges' => $additionalCharges,
            'discount' => $discount,
            'total_price' => $totalPrice,
        ];
    }

    // ─── Customer Validation ──────────────────────────────────────────

    private function findUserOrFail(int $userId): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw new \InvalidArgumentException('The specified user does not exist.');
        }

        return $user;
    }

    private function validateCustomer(User $user): void
    {
        if (!$user->isCustomer() && !$user->isStaff() && !$user->isAdmin()) {
            throw new \InvalidArgumentException('User is not authorized to create bookings.');
        }
    }

    // ─── Vehicle Validation ──────────────────────────────────────────

    private function findVehicleOrFail(int $vehicleId): Vehicle
    {
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            throw new \InvalidArgumentException('The selected vehicle does not exist.');
        }

        return $vehicle;
    }

    private function validateVehicle(Vehicle $vehicle): void
    {
        if (!$vehicle->branch_id) {
            throw new \InvalidArgumentException('Vehicle is not assigned to a branch and cannot be booked.');
        }

        $branch = $vehicle->branch;
        if ($branch && !$branch->isActive()) {
            throw new \InvalidArgumentException('Bookings are not available for inactive branches.');
        }

        if ($vehicle->status === 'maintenance') {
            throw new \InvalidArgumentException('Vehicle is currently under maintenance and cannot be booked.');
        }

        if ($vehicle->status === 'unavailable') {
            throw new \InvalidArgumentException('Vehicle is marked as unavailable and cannot be booked.');
        }

        if ($vehicle->status !== 'available') {
            throw new \InvalidArgumentException('Vehicle is not available for booking.');
        }
    }

    // ─── Date Validation ─────────────────────────────────────────────

    private function validateDates(Carbon $pickupDate, Carbon $returnDate): void
    {
        $today = Carbon::today();

        if ($pickupDate->lt($today)) {
            throw new \InvalidArgumentException('Pickup date cannot be in the past.');
        }

        if ($returnDate->lte($pickupDate)) {
            throw new \InvalidArgumentException('Return date must be after pickup date.');
        }
    }

    private function validateNoOverlap(int $vehicleId, Carbon $pickupDate, Carbon $returnDate, ?int $excludeBookingId = null): void
    {
        if ($this->hasOverlap($vehicleId, $pickupDate, $returnDate, $excludeBookingId)) {
            throw new \InvalidArgumentException('Vehicle is already booked for the selected dates.');
        }
    }

    // ─── Pricing Calculation ─────────────────────────────────────────

    private function calculateNumberOfDays(Carbon $pickupDate, Carbon $returnDate): int
    {
        return max(1, $pickupDate->diffInDays($returnDate));
    }

    private function getPricePerDay(Vehicle $vehicle): float
    {
        return (float) $vehicle->rental_price_per_day;
    }

    private function calculateSubtotal(int $numberOfDays, float $pricePerDay): float
    {
        return round($numberOfDays * $pricePerDay, 2);
    }

    private function resolveAdditionalCharges(array $data): float
    {
        return (float) ($data['additional_charges'] ?? 0);
    }

    private function resolveDiscount(array $data): float
    {
        return (float) ($data['discount'] ?? 0);
    }

    private function calculateTotalPrice(float $subtotal, float $additionalCharges, float $discount): float
    {
        return round($subtotal + $additionalCharges - $discount, 2);
    }

    // ─── Reference Generation ────────────────────────────────────────

    private function generateUniqueReference(): string
    {
        for ($attempt = 1; $attempt <= self::MAX_REFERENCE_RETRIES; $attempt++) {
            $reference = $this->generateReference();

            if (!Booking::where('booking_reference', $reference)->exists()) {
                return $reference;
            }

            Log::warning('Booking reference collision detected', [
                'reference' => $reference,
                'attempt' => $attempt,
            ]);
        }

        throw new \RuntimeException('Failed to generate a unique booking reference after ' . self::MAX_REFERENCE_RETRIES . ' attempts.');
    }

    private function generateReference(): string
    {
        $prefix = 'BOOK-' . now()->format('Ymd');
        $sequence = strtoupper(Str::random(4));
        $random = strtoupper(Str::random(4));

        return $prefix . '-' . $sequence . '-' . $random;
    }
}
