<?php

namespace App\Services;

use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleTransfer;
use App\Notifications\BookingBranchApprovedAwaitingPayment;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\DriverLicenseService;
use Throwable;

class BookingService
{
    private const MAX_REFERENCE_RETRIES = 5;

    public function __construct(
        private BookingWorkflowService $workflow,
        private DriverLicenseService $licenseService
    ) {}

    public function createBooking(array $data, int $userId): Booking
    {
        $user = $this->findUserOrFail($userId);
        $this->validateCustomer($user);

        $vehicle = $this->findVehicleOrFail($data['vehicle_id']);
        $this->validateVehicle($vehicle);

        // License eligibility check — only for customer-initiated bookings.
        // Staff/admin creating bookings on behalf of customers bypass this check.
        if ($user->isCustomer()) {
            $this->validateLicenseEligibility($user, $vehicle);
        }

        if (!empty($data['branch_id']) && (int) $data['branch_id'] !== (int) $vehicle->branch_id) {
            throw new \InvalidArgumentException('The selected vehicle does not belong to the chosen branch.');
        }

        $pickupDate = Carbon::parse($data['pickup_date']);
        $returnDate = Carbon::parse($data['return_date']);
        $this->validateDates($pickupDate, $returnDate);

        // An exact retry is idempotent. Other overlaps, including another
        // interval from the same customer, are availability conflicts.
        $existingBooking = $this->findExactDuplicate(
            $userId,
            $vehicle->id,
            $pickupDate,
            $returnDate,
        );

        if ($existingBooking) {
            return $this->markAsExistingBooking($existingBooking, $userId, $vehicle->id);
        }

        // Give normal conflicts a clear 422 before attempting the insert. The
        // PostgreSQL exclusion constraint is the final concurrency guard.
        $this->validateNoOverlap($vehicle->id, $pickupDate, $returnDate);

        $numberOfDays = $this->calculateNumberOfDays($pickupDate, $returnDate);
        $pricePerDay = $this->getPricePerDay($vehicle);
        $subtotal = $this->calculateSubtotal($numberOfDays, $pricePerDay);
        $additionalCharges = $this->resolveAdditionalCharges($data);
        $discount = $this->resolveDiscount($data);
        $totalPrice = $this->calculateTotalPrice($subtotal, $additionalCharges, $discount);
        $draft = new Booking([
            'total_price' => $totalPrice,
            'number_of_days' => $numberOfDays,
            'subtotal' => $subtotal,
            'discount' => $discount,
        ]);
        $approvals = $this->workflow->resolveInitialApprovals($draft);

        $branchAutoApproved = $approvals['branch_approval_status'] !== Booking::APPROVAL_PENDING;
        $initialStatus = Booking::STATUS_PENDING_BRANCH_APPROVAL;
        $initialPaymentStatus = Booking::PAYMENT_STATUS_NOT_REQUIRED;

        if ($branchAutoApproved) {
            if ($approvals['admin_approval_required']) {
                $initialStatus = Booking::STATUS_PENDING_ADMIN_APPROVAL;
            } else {
                // Operationally approved — payment is required to confirm the booking.
                $initialStatus = Booking::STATUS_PAYMENT_REQUIRED;
                $initialPaymentStatus = Booking::PAYMENT_STATUS_PENDING;
            }
        }

        $attributes = [
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
            'status' => $initialStatus,
            'payment_status' => $initialPaymentStatus,
            'branch_approval_status' => $approvals['branch_approval_status'],
            'admin_approval_status' => $approvals['admin_approval_status'],
            'admin_approval_required' => $approvals['admin_approval_required'],
            'notes' => $data['notes'] ?? null,
        ];

        $booking = $this->insertBooking(
            $attributes,
            $userId,
            $vehicle->id,
            $pickupDate,
            $returnDate,
        );

        if (! (bool) $booking->getAttribute('booking_was_created')) {
            return $booking;
        }

        // The booking row is authoritative for date availability. Vehicle
        // status is a display/operations aid and must not turn a committed
        // booking into a false 500 response if that secondary update fails.
        if (in_array($initialStatus, [Booking::STATUS_PAYMENT_REQUIRED, Booking::STATUS_PENDING_ADMIN_APPROVAL], true)) {
            try {
                $updated = Vehicle::query()
                    ->whereKey($vehicle->id)
                    ->whereIn('status', [Vehicle::STATUS_AVAILABLE, Vehicle::STATUS_RESERVED])
                    ->update(['status' => Vehicle::STATUS_RESERVED]);

                if ($updated !== 1) {
                    Log::warning('[Booking] Created but vehicle status was not reserved', [
                        'booking_id' => $booking->id,
                        'vehicle_id' => $vehicle->id,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('[Booking] Created but vehicle reservation update failed', [
                    'booking_id' => $booking->id,
                    'vehicle_id' => $vehicle->id,
                    'exception_class' => $e::class,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $booking->load('vehicle', 'user', 'branch');

        try {
            event(new BookingCreated($booking));
        } catch (Throwable $e) {
            Log::error('[Booking] Created but BookingCreated dispatch failed', [
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'vehicle_id' => $vehicle->id,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        // After automatic operational approval, the customer must immediately know payment is required.
        if ($booking->status === Booking::STATUS_PAYMENT_REQUIRED && $booking->payment_status === Booking::PAYMENT_STATUS_PENDING) {
            try {
                $booking->user->notify(new BookingBranchApprovedAwaitingPayment($booking));
            } catch (Throwable $e) {
                Log::warning('[Booking] Created but payment-required notification failed', [
                    'booking_id' => $booking->id,
                    'user_id' => $userId,
                    'exception_class' => $e::class,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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

    /**
     * Insert a booking without opening an explicit application transaction.
     * PostgreSQL's exclusion constraint atomically rejects concurrent date
     * overlaps; exact retries return the row created by the winning request.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function insertBooking(
        array $attributes,
        int $userId,
        int $vehicleId,
        Carbon $pickupDate,
        Carbon $returnDate,
    ): Booking {
        for ($attempt = 1; $attempt <= self::MAX_REFERENCE_RETRIES; $attempt++) {
            $attributes['booking_reference'] = $this->generateUniqueReference();

            try {
                $booking = Booking::create($attributes);
                $booking->setAttribute('booking_was_created', true);
                $booking->syncOriginalAttribute('booking_was_created');

                return $booking;
            } catch (QueryException $e) {
                if ($this->isOverlapConstraintViolation($e)) {
                    $existing = $this->findExactDuplicate(
                        $userId,
                        $vehicleId,
                        $pickupDate,
                        $returnDate,
                    );

                    if ($existing) {
                        return $this->markAsExistingBooking($existing, $userId, $vehicleId);
                    }

                    Log::info('[Booking] Concurrent overlapping booking blocked', [
                        'user_id' => $userId,
                        'vehicle_id' => $vehicleId,
                        'pickup_date' => $pickupDate->toDateTimeString(),
                        'return_date' => $returnDate->toDateTimeString(),
                    ]);

                    throw new \InvalidArgumentException(
                        'This vehicle is unavailable for the selected dates.',
                        previous: $e,
                    );
                }

                if ($this->isBookingReferenceCollision($e) && $attempt < self::MAX_REFERENCE_RETRIES) {
                    Log::warning('[Booking] Reference collision during insert', [
                        'attempt' => $attempt,
                        'vehicle_id' => $vehicleId,
                    ]);

                    continue;
                }

                Log::error('[Booking] Database insert failed', [
                    'user_id' => $userId,
                    'vehicle_id' => $vehicleId,
                    'stage' => 'insert_booking',
                    'sql_state' => $e->errorInfo[0] ?? $e->getCode(),
                    'exception_class' => $e::class,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        throw new \RuntimeException('Failed to create a unique booking reference.');
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
        $actor = $actor ?? Auth::user();
        /** @var \App\Models\User $actor */
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
        $actor = $actor ?? Auth::user();
        /** @var \App\Models\User $actor */
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
        /** @var \App\Models\User $actor */
        return $this->workflow->cancelBooking($booking, $actor ?? Auth::user(), $reason, $source);
    }

    public function markAsPickedUp(Booking $booking, ?User $actor = null, array $data = []): Booking
    {
        $actor = $actor ?? Auth::user();
        /** @var \App\Models\User $actor */

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
        $actor = $actor ?? Auth::user();
        /** @var \App\Models\User $actor */
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

    private function validateLicenseEligibility(User $customer, Vehicle $vehicle): void
    {
        // If the vehicle does not require a license, skip the check.
        if (isset($vehicle->requires_license) && !$vehicle->requires_license) {
            return;
        }

        $eligibility = $this->licenseService->checkEligibility($customer, $vehicle);

        if (!$eligibility['eligible']) {
            throw new \InvalidArgumentException($eligibility['reason']);
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

        if (VehicleTransfer::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', VehicleTransfer::ACTIVE_STATUSES)
            ->exists()) {
            throw new \InvalidArgumentException('Vehicle is not available for booking because it has an active transfer.');
        }

        if (!$vehicle->isRentable()) {
            if ($vehicle->hasExpiredRequiredDocuments()) {
                throw new \InvalidArgumentException('Vehicle has expired required documents and cannot be booked.');
            }

            if ($vehicle->status === Vehicle::STATUS_MAINTENANCE) {
                throw new \InvalidArgumentException('Vehicle is currently under maintenance and cannot be booked.');
            }

            if (in_array($vehicle->status, [
                Vehicle::STATUS_UNAVAILABLE,
                Vehicle::STATUS_RETIRED,
                Vehicle::STATUS_TRANSFER_PENDING,
                Vehicle::STATUS_TRANSFER_IN_TRANSIT,
            ], true)) {
                throw new \InvalidArgumentException('Vehicle is not available for booking.');
            }

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
            throw new \InvalidArgumentException('This vehicle is unavailable for the selected dates.');
        }
    }

    private function findExactDuplicate(
        int $userId,
        int $vehicleId,
        Carbon $pickupDate,
        Carbon $returnDate,
    ): ?Booking {
        return Booking::query()
            ->where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', Booking::BLOCKING_STATUSES)
            ->where('pickup_date', $pickupDate)
            ->where('return_date', $returnDate)
            ->first();
    }

    private function markAsExistingBooking(Booking $booking, int $userId, int $vehicleId): Booking
    {
        Log::info('[Booking] Exact duplicate blocked; returning existing booking', [
            'existing_booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'user_id' => $userId,
            'vehicle_id' => $vehicleId,
        ]);

        $booking->load('vehicle', 'user', 'branch');
        $booking->setAttribute('booking_was_created', false);
        $booking->syncOriginalAttribute('booking_was_created');

        return $booking;
    }

    private function isOverlapConstraintViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

        return $sqlState === '23P01'
            && str_contains(strtolower($e->getMessage()), 'bookings_vehicle_dates_no_overlap');
    }

    private function isBookingReferenceCollision(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $message = strtolower($e->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            && (str_contains($message, 'booking_reference')
                || str_contains($message, 'bookings_booking_reference_unique'));
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
