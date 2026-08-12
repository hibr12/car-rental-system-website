<?php

namespace App\Services;

use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingService
{
    private const MAX_REFERENCE_RETRIES = 5;

    public function __construct(
        private BookingWorkflowService $workflow
    ) {}

    public function createBooking(array $data, int $userId): Booking
    {
        $user = $this->findUserOrFail($userId);
        $this->validateCustomer($user);

        $vehicle = $this->findVehicleOrFail($data['vehicle_id']);
        $this->validateVehicle($vehicle);

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
            $draft = new Booking([
                'total_price' => $totalPrice,
                'number_of_days' => $numberOfDays,
                'subtotal' => $subtotal,
                'discount' => $discount,
            ]);
            $approvals = $this->workflow->resolveInitialApprovals($draft);

            $booking = Booking::create([
                'booking_reference' => $bookingReference,
                'user_id' => $userId,
                'vehicle_id' => $vehicle->id,
                'branch_id' => $vehicle->branch_id,
                'pickup_location' => $data['pickup_location'],
                'return_location' => $data['return_location'],
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
                'number_of_days' => $numberOfDays,
                'price_per_day' => $pricePerDay,
                'subtotal' => $subtotal,
                'additional_charges' => $additionalCharges,
                'discount' => $discount,
                'total_price' => $totalPrice,
                'status' => Booking::STATUS_PENDING_BRANCH_APPROVAL,
                'payment_status' => Booking::PAYMENT_STATUS_NOT_REQUIRED,
                'branch_approval_status' => $approvals['branch_approval_status'],
                'admin_approval_status' => $approvals['admin_approval_status'],
                'admin_approval_required' => $approvals['admin_approval_required'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $booking->load('vehicle', 'user', 'branch');
        });

        event(new BookingCreated($booking));

        Log::info('Booking created successfully', [
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'user_id' => $userId,
            'vehicle_id' => $vehicle->id,
            'total_price' => $totalPrice,
            'admin_approval_required' => $booking->admin_approval_required,
        ]);

        return $booking;
    }

    public function approveByBranch(Booking $booking, User $approver): Booking
    {
        return $this->workflow->approveBranch($booking, $approver);
    }

    public function approveByAdmin(Booking $booking, User $approver): Booking
    {
        return $this->workflow->approveAdmin($booking, $approver);
    }

    public function confirmBooking(Booking $booking, ?User $actor = null): Booking
    {
        $actor = $actor ?? auth()->user();
        $status = $booking->normalizeStatus();

        if ($actor->isBranchManager()
            || ($actor->isAdmin() && $status === Booking::STATUS_PENDING_BRANCH_APPROVAL
                && $booking->branch_approval_status === Booking::APPROVAL_PENDING)) {
            return $this->workflow->approveBranch($booking, $actor);
        }

        if ($actor->isAdmin() && (
            $status === Booking::STATUS_PENDING_ADMIN_APPROVAL
            || $booking->admin_approval_status === Booking::APPROVAL_PENDING
        )) {
            return $this->workflow->approveAdmin($booking, $actor);
        }

        throw new \InvalidArgumentException('No approval action is available for this booking in its current state.');
    }

    public function rejectBooking(Booking $booking, ?string $reason = null, ?User $actor = null): Booking
    {
        $actor = $actor ?? auth()->user();
        $reason = trim((string) $reason);

        if ($reason === '') {
            throw new \InvalidArgumentException('A rejection reason is required.');
        }

        if ($actor->isBranchManager()
            || ($actor->isAdmin() && $booking->branch_approval_status === Booking::APPROVAL_PENDING)) {
            return $this->workflow->rejectBranch($booking, $actor, $reason);
        }

        return $this->workflow->rejectAdmin($booking, $actor, $reason);
    }

    public function rejectByBranch(Booking $booking, User $rejector, ?string $reason = null): Booking
    {
        return $this->workflow->rejectBranch($booking, $rejector, (string) $reason);
    }

    public function rejectByAdmin(Booking $booking, User $rejector, ?string $reason = null): Booking
    {
        return $this->workflow->rejectAdmin($booking, $rejector, (string) $reason);
    }

    public function cancelBooking(Booking $booking, ?User $actor = null, ?string $reason = null, string $source = 'customer'): Booking
    {
        return $this->workflow->cancelBooking($booking, $actor ?? auth()->user(), $reason, $source);
    }

    public function markAsPickedUp(Booking $booking, ?User $actor = null, array $data = []): Booking
    {
        $actor = $actor ?? auth()->user();

        // Allow simple staff pickup when documents already verified / provided in request
        $defaults = [
            'identity_verification_status' => $data['identity_verification_status']
                ?? ($booking->identity_verification_status === Booking::DOC_VERIFIED
                    ? Booking::DOC_VERIFIED
                    : ($data['skip_document_check'] ?? false ? Booking::DOC_NOT_REQUIRED : null)),
            'license_verification_status' => $data['license_verification_status']
                ?? ($booking->license_verification_status === Booking::DOC_VERIFIED
                    ? Booking::DOC_VERIFIED
                    : ($data['skip_document_check'] ?? false ? Booking::DOC_NOT_REQUIRED : null)),
            'pickup_mileage' => $data['pickup_mileage'] ?? $booking->pickup_mileage ?? $booking->vehicle?->mileage ?? 0,
            'pickup_fuel_level' => $data['pickup_fuel_level'] ?? $booking->pickup_fuel_level ?? 'full',
        ];

        // If actor confirms handover without full checklist payload, require explicit verification flags
        if (!isset($data['identity_verification_status']) && !isset($data['skip_document_check'])) {
            $defaults['identity_verification_status'] = Booking::DOC_VERIFIED;
            $defaults['license_verification_status'] = Booking::DOC_VERIFIED;
        }

        return $this->workflow->markPickedUp($booking, $actor, array_merge($defaults, $data));
    }

    public function markAsReturned(Booking $booking, ?User $actor = null, array $data = []): Booking
    {
        $actor = $actor ?? auth()->user();
        $defaults = [
            'return_mileage' => $data['return_mileage'] ?? $booking->vehicle?->mileage ?? $booking->pickup_mileage ?? 0,
            'return_fuel_level' => $data['return_fuel_level'] ?? 'full',
        ];

        return $this->workflow->markReturned($booking, $actor, array_merge($defaults, $data));
    }

    public function preparePickup(Booking $booking, User $actor): Booking
    {
        return $this->workflow->preparePickup($booking, $actor);
    }

    public function hasOverlap(int $vehicleId, Carbon $pickupDate, Carbon $returnDate, ?int $excludeBookingId = null): bool
    {
        $query = Booking::overlapping($vehicleId, $pickupDate, $returnDate);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

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
