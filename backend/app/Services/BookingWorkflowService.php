<?php

namespace App\Services;

use App\Events\BookingCancelled;
use App\Events\BookingCompleted;
use App\Events\BookingConfirmed;
use App\Events\BookingPickedUp;
use App\Events\BookingRejected;
use App\Notifications\BookingBranchApprovedAwaitingPayment;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleDamageService;
use App\Services\VehicleInspectionService;
use App\Services\VehicleStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Central booking state machine. All lifecycle transitions go through here.
 */
class BookingWorkflowService
{
    public function __construct(
        private AuditLogService $auditLogService,
        private VehicleInspectionService $inspectionService,
        private VehicleStatusService $vehicleStatusService
    ) {}

    public function requiresAdminApproval(Booking $booking): bool
    {
        if ($booking->admin_approval_required) {
            return true;
        }

        $rules = config('booking.admin_approval', []);
        $highValue = (float) ($rules['high_value_threshold'] ?? 50000);
        $longDays = (int) ($rules['long_rental_days'] ?? 14);
        $discountPct = (float) ($rules['discount_percent_threshold'] ?? 20);

        if ((float) $booking->total_price >= $highValue) {
            return true;
        }

        if ((int) $booking->number_of_days >= $longDays) {
            return true;
        }

        $subtotal = (float) $booking->subtotal;
        if ($subtotal > 0 && ((float) $booking->discount / $subtotal) * 100 >= $discountPct) {
            return true;
        }

        return false;
    }

    public function resolveInitialApprovals(Booking $booking): array
    {
        $needsAdmin = $this->requiresAdminApproval($booking);

        return [
            // Branch operational approval is automatic for normal bookings.
            // Branch manager intervention is only needed for exceptions we already
            // route through "admin approval required" thresholds.
            'branch_approval_status' => $needsAdmin
                ? Booking::APPROVAL_PENDING
                : Booking::APPROVAL_NOT_REQUIRED,
            'admin_approval_status' => $needsAdmin ? Booking::APPROVAL_PENDING : Booking::APPROVAL_NOT_REQUIRED,
            'admin_approval_required' => $needsAdmin,
        ];
    }

    /**
     * After payment becomes PAID + verified / manually confirmed.
     * Confirms booking when branch (and admin, if required) already approved.
     */
    public function advanceAfterPaymentVerified(Booking $booking, ?User $actor = null): Booking
    {
        $booking->refresh();
        $booking->load('payments');

        if (!$booking->isPaymentSatisfied()) {
            throw new \InvalidArgumentException('Payment must be paid and verified before advancing the booking.');
        }

        $status = $booking->normalizeStatus();

        if (in_array($status, [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_READY_FOR_PICKUP,
            Booking::STATUS_ACTIVE,
            Booking::STATUS_RETURN_PENDING,
            Booking::STATUS_COMPLETED,
        ], true)) {
            return $booking;
        }

        if (in_array($status, [Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED, Booking::STATUS_EXPIRED], true)) {
            throw new \InvalidArgumentException('Cannot advance a cancelled, rejected, or expired booking after payment.');
        }

        // Legacy payment-before-approval: paid while still awaiting branch — do not auto-confirm.
        if ($status === Booking::STATUS_PENDING_BRANCH_APPROVAL
            && $booking->branch_approval_status === Booking::APPROVAL_PENDING) {
            Log::warning('Payment verified before branch approval — requires reconciliation', [
                'booking_id' => $booking->id,
                'reference' => $booking->booking_reference,
            ]);

            return $booking;
        }

        if (!$booking->isBranchApproved()) {
            throw new \InvalidArgumentException('Branch approval is required before confirming a paid booking.');
        }

        if (!$booking->isAdminApproved()) {
            throw new \InvalidArgumentException('Admin approval is required before confirming a paid booking.');
        }

        if (!in_array($status, [
            Booking::STATUS_PAYMENT_REQUIRED,
            Booking::STATUS_PAYMENT_PROCESSING,
            Booking::STATUS_PAYMENT_VERIFIED,
            Booking::STATUS_PENDING_PAYMENT,
            Booking::STATUS_PENDING_ADMIN_APPROVAL,
        ], true)) {
            return $booking;
        }

        return $this->confirmBooking($booking, $actor);
    }

    public function approveBranch(Booking $booking, User $approver): Booking
    {
        $this->assertCanApproveBranch($booking, $approver);

        $status = $booking->normalizeStatus();
        if (!in_array($status, [
            Booking::STATUS_PENDING_BRANCH_APPROVAL,
            Booking::STATUS_PAYMENT_VERIFIED,
            Booking::STATUS_PENDING,
            Booking::STATUS_BRANCH_REVIEW,
        ], true)) {
            throw new \InvalidArgumentException('Booking is not awaiting branch approval.');
        }

        if ($booking->branch_approval_status === Booking::APPROVAL_APPROVED) {
            throw new \InvalidArgumentException('Booking is already branch-approved.');
        }

        if ($booking->branch_approval_status === Booking::APPROVAL_REJECTED) {
            throw new \InvalidArgumentException('Booking was rejected by the branch.');
        }

        $this->assertVehicleAvailableForConfirmation($booking);

        return DB::transaction(function () use ($booking, $approver) {
            $old = $booking->status;
            $needsAdmin = $booking->admin_approval_required
                || $booking->admin_approval_status === Booking::APPROVAL_PENDING;

            $updates = [
                'branch_approval_status' => Booking::APPROVAL_APPROVED,
                'branch_approved_at' => now(),
                'branch_approved_by' => $approver->id,
            ];

            if ($needsAdmin && $booking->admin_approval_status !== Booking::APPROVAL_APPROVED) {
                $updates['admin_approval_status'] = Booking::APPROVAL_PENDING;
                $updates['admin_approval_required'] = true;
                $updates['status'] = Booking::STATUS_PENDING_ADMIN_APPROVAL;
                $updates['payment_status'] = $booking->isPaymentSatisfied()
                    ? Booking::PAYMENT_STATUS_PAID
                    : Booking::PAYMENT_STATUS_NOT_REQUIRED;
            } elseif ($booking->isPaymentSatisfied()) {
                $updates['admin_approval_status'] = Booking::APPROVAL_NOT_REQUIRED;
                $updates['admin_approval_required'] = false;
                $updates['status'] = Booking::STATUS_CONFIRMED;
                $updates['payment_status'] = Booking::PAYMENT_STATUS_PAID;
            } else {
                $updates['admin_approval_status'] = Booking::APPROVAL_NOT_REQUIRED;
                $updates['admin_approval_required'] = false;
                $updates['status'] = Booking::STATUS_PAYMENT_REQUIRED;
                $updates['payment_status'] = Booking::PAYMENT_STATUS_PENDING;
            }

            $booking->update($updates);

            $booking->vehicle->update(['status' => Vehicle::STATUS_RESERVED]);

            if ($booking->status === Booking::STATUS_CONFIRMED) {
                $this->maybeMarkReadyForPickup($booking);
            }

            $fresh = $booking->fresh()->load(['vehicle', 'user', 'branch', 'payments']);

            $this->audit($approver, $fresh, 'branch_approved', $old, $fresh->status, $booking->isPaymentSatisfied()
                ? 'Branch approved booking — already paid'
                : 'Branch approved booking — payment now required');

            // Customer needs clear next step immediately after branch approval.
            // Only notify when the booking is still waiting on payment (or final admin confirmation).
            if (in_array($fresh->status, [
                Booking::STATUS_PAYMENT_REQUIRED,
                Booking::STATUS_PAYMENT_PROCESSING,
                Booking::STATUS_PENDING_ADMIN_APPROVAL,
            ], true)) {
                try {
                    $fresh->user->notify(new BookingBranchApprovedAwaitingPayment($fresh));
                } catch (\Throwable) {
                    // Notifications must never break business workflow transitions.
                }
            }

            if ($fresh->status === Booking::STATUS_CONFIRMED || $fresh->status === Booking::STATUS_READY_FOR_PICKUP) {
                event(new BookingConfirmed($fresh));
            }

            Log::info('Branch approved booking', [
                'booking_id' => $fresh->id,
                'approver_id' => $approver->id,
                'new_status' => $fresh->status,
            ]);

            return $fresh;
        });
    }

    public function rejectBranch(Booking $booking, User $rejector, string $reason): Booking
    {
        $this->assertCanApproveBranch($booking, $rejector);

        if (strlen(trim($reason)) < 3) {
            throw new \InvalidArgumentException('A rejection reason is required.');
        }

        $status = $booking->normalizeStatus();
        if (!in_array($status, [
            Booking::STATUS_PENDING_BRANCH_APPROVAL,
            Booking::STATUS_PAYMENT_VERIFIED,
            Booking::STATUS_PENDING,
            Booking::STATUS_BRANCH_REVIEW,
            Booking::STATUS_PENDING_PAYMENT,
        ], true)) {
            throw new \InvalidArgumentException('Booking cannot be branch-rejected in its current state.');
        }

        if ($booking->isPaymentSatisfied()) {
            throw new \InvalidArgumentException('Paid bookings cannot be rejected. Use cancel & refund instead.');
        }

        return $this->performRejection($booking, $rejector, 'branch', $reason);
    }

    public function approveAdmin(Booking $booking, User $approver): Booking
    {
        if (!$approver->isAdmin()) {
            throw new \InvalidArgumentException('Only administrators can give admin approval.');
        }

        $status = $booking->normalizeStatus();
        if ($status !== Booking::STATUS_PENDING_ADMIN_APPROVAL
            && !($status === Booking::STATUS_PENDING_BRANCH_APPROVAL && $booking->branch_approval_status === Booking::APPROVAL_APPROVED)) {
            throw new \InvalidArgumentException('Booking is not awaiting admin approval.');
        }

        if ($booking->admin_approval_status === Booking::APPROVAL_APPROVED) {
            throw new \InvalidArgumentException('Booking is already admin-approved.');
        }

        if ($booking->branch_approval_status !== Booking::APPROVAL_APPROVED
            && $booking->branch_approval_status !== Booking::APPROVAL_NOT_REQUIRED) {
            throw new \InvalidArgumentException('Branch approval is still pending.');
        }

        $this->assertVehicleAvailableForConfirmation($booking);
        $this->assertNotTerminal($booking);

        return DB::transaction(function () use ($booking, $approver) {
            $old = $booking->status;

            $booking->update([
                'admin_approval_status' => Booking::APPROVAL_APPROVED,
                'admin_approved_at' => now(),
                'admin_approved_by' => $approver->id,
                'admin_approval_required' => true,
                'status' => $booking->isPaymentSatisfied()
                    ? Booking::STATUS_CONFIRMED
                    : Booking::STATUS_PAYMENT_REQUIRED,
                'payment_status' => $booking->isPaymentSatisfied()
                    ? Booking::PAYMENT_STATUS_PAID
                    : Booking::PAYMENT_STATUS_PENDING,
            ]);

            $booking->vehicle->update(['status' => Vehicle::STATUS_RESERVED]);

            if ($booking->status === Booking::STATUS_CONFIRMED) {
                $this->maybeMarkReadyForPickup($booking);
            }

            $fresh = $booking->fresh()->load(['vehicle', 'user', 'branch', 'payments']);

            $this->audit($approver, $fresh, 'admin_approved', $old, $fresh->status, $booking->isPaymentSatisfied()
                ? 'Admin approved — booking confirmed'
                : 'Admin approved — payment now required');

            if ($fresh->status === Booking::STATUS_CONFIRMED || $fresh->status === Booking::STATUS_READY_FOR_PICKUP) {
                event(new BookingConfirmed($fresh));
            } elseif (in_array($fresh->status, [
                Booking::STATUS_PAYMENT_REQUIRED,
                Booking::STATUS_PAYMENT_PROCESSING,
            ], true)) {
                try {
                    $fresh->user->notify(new BookingBranchApprovedAwaitingPayment($fresh, 'admin'));
                } catch (\Throwable) {
                    // Notifications must never break business workflow transitions.
                }
            }

            return $fresh;
        });
    }

    public function rejectAdmin(Booking $booking, User $rejector, string $reason): Booking
    {
        if (!$rejector->isAdmin()) {
            throw new \InvalidArgumentException('Only administrators can reject at admin level.');
        }

        if (strlen(trim($reason)) < 3) {
            throw new \InvalidArgumentException('A rejection reason is required.');
        }

        $status = $booking->normalizeStatus();
        if (!in_array($status, [
            Booking::STATUS_PENDING_ADMIN_APPROVAL,
            Booking::STATUS_PENDING_BRANCH_APPROVAL,
            Booking::STATUS_PENDING,
            Booking::STATUS_BRANCH_REVIEW,
        ], true)) {
            throw new \InvalidArgumentException('Booking cannot be admin-rejected in its current state.');
        }

        return $this->performRejection($booking, $rejector, 'admin', $reason);
    }

    /**
     * Confirm only when all gates pass. Used for explicit confirm / after approvals.
     */
    public function confirmBooking(Booking $booking, ?User $actor = null): Booking
    {
        if (!$booking->canBecomeConfirmed()) {
            $reasons = [];
            if (!$booking->isPaymentSatisfied()) {
                $reasons[] = 'payment is not verified';
            }
            if (!$booking->isBranchApproved()) {
                $reasons[] = 'branch approval is still pending';
            }
            if (!$booking->isAdminApproved()) {
                $reasons[] = 'admin approval is still pending';
            }
            throw new \InvalidArgumentException(
                'Booking cannot be confirmed because ' . implode(', ', $reasons) . '.'
            );
        }

        $this->assertVehicleAvailableForConfirmation($booking);

        return DB::transaction(function () use ($booking, $actor) {
            $old = $booking->status;
            $booking->update(['status' => Booking::STATUS_CONFIRMED]);
            $booking->vehicle->update(['status' => Vehicle::STATUS_RESERVED]);
            $this->maybeMarkReadyForPickup($booking);

            $fresh = $booking->fresh()->load(['vehicle', 'user', 'branch', 'payments']);
            $this->audit($actor, $fresh, 'booking_confirmed', $old, $fresh->status);
            event(new BookingConfirmed($fresh));

            return $fresh;
        });
    }

    public function preparePickup(Booking $booking, User $actor): Booking
    {
        $this->assertBranchAccess($actor, $booking);

        $status = $booking->normalizeStatus();
        if ($status !== Booking::STATUS_CONFIRMED) {
            throw new \InvalidArgumentException('Only confirmed bookings can be prepared for pickup.');
        }

        $this->assertReadyForOperationalActions($booking);

        return DB::transaction(function () use ($booking, $actor) {
            $old = $booking->status;
            $booking->update(['status' => Booking::STATUS_READY_FOR_PICKUP]);
            if ($booking->vehicle && $booking->vehicle->status === Vehicle::STATUS_RESERVED) {
                $this->vehicleStatusService->transition(
                    $booking->vehicle,
                    Vehicle::STATUS_READY_FOR_PICKUP,
                    $actor,
                    'Booking prepared for pickup',
                    true
                );
            }
            $fresh = $booking->fresh()->load(['vehicle', 'user', 'branch', 'payments']);
            $this->audit($actor, $fresh, 'pickup_prepared', $old, $fresh->status, 'Marked ready for pickup');

            return $fresh;
        });
    }

    public function markPickedUp(Booking $booking, User $actor, array $data = []): Booking
    {
        $this->assertBranchAccess($actor, $booking);

        $status = $booking->normalizeStatus();
        if (!in_array($status, [Booking::STATUS_CONFIRMED, Booking::STATUS_READY_FOR_PICKUP], true)) {
            throw new \InvalidArgumentException('Only confirmed or ready-for-pickup bookings can be picked up.');
        }

        $this->assertReadyForOperationalActions($booking);

        if (!$this->isPickupTimingSatisfied($booking)) {
            throw new \InvalidArgumentException(
                'Booking cannot be picked up yet — pickup window has not opened. Prepare pickup first or wait until closer to the pickup date.'
            );
        }

        $identity = $data['identity_verification_status'] ?? $booking->identity_verification_status;
        $license = $data['license_verification_status'] ?? $booking->license_verification_status;

        if (($identity ?? Booking::DOC_UNVERIFIED) !== Booking::DOC_VERIFIED
            && ($identity ?? '') !== Booking::DOC_NOT_REQUIRED) {
            throw new \InvalidArgumentException('Customer identity must be verified before vehicle handover.');
        }

        if (($license ?? Booking::DOC_UNVERIFIED) !== Booking::DOC_VERIFIED
            && ($license ?? '') !== Booking::DOC_NOT_REQUIRED) {
            throw new \InvalidArgumentException('Driver license must be verified before vehicle handover.');
        }

        if (!isset($data['pickup_mileage']) && !$booking->pickup_mileage) {
            throw new \InvalidArgumentException('Pickup mileage is required for vehicle handover.');
        }

        if (empty($data['pickup_fuel_level']) && !$booking->pickup_fuel_level) {
            throw new \InvalidArgumentException('Pickup fuel level is required for vehicle handover.');
        }

        return DB::transaction(function () use ($booking, $actor, $data, $identity, $license) {
            $old = $booking->status;

            $booking->update([
                'status' => Booking::STATUS_ACTIVE,
                'identity_verification_status' => $identity,
                'license_verification_status' => $license,
                'documents_verified_at' => now(),
                'documents_verified_by' => $actor->id,
                'picked_up_by' => $actor->id,
                'picked_up_at' => now(),
                'pickup_branch_id' => $booking->branch_id,
                'pickup_mileage' => $data['pickup_mileage'] ?? $booking->pickup_mileage,
                'pickup_fuel_level' => $data['pickup_fuel_level'] ?? $booking->pickup_fuel_level,
                'notes' => $this->appendNote($booking->notes, 'PICKUP', array_merge($data, [
                    'picked_up_by' => $actor->id,
                    'picked_up_at' => now()->toIso8601String(),
                ])),
            ]);

            $vehicleUpdate = ['status' => Vehicle::STATUS_RENTED];
            if (isset($data['pickup_mileage'])) {
                $vehicleUpdate['mileage'] = (int) $data['pickup_mileage'];
            }
            $booking->vehicle->update($vehicleUpdate);

            $fresh = $booking->fresh()->load(['vehicle', 'user', 'branch', 'payments']);
            $this->audit($actor, $fresh, 'vehicle_picked_up', $old, $fresh->status, 'Vehicle handed over to customer');
            event(new BookingPickedUp($fresh));

            return $fresh;
        });
    }

    public function markReturned(Booking $booking, User $actor, array $data = []): Booking
    {
        $this->assertBranchAccess($actor, $booking);

        $status = $booking->normalizeStatus();
        if ($status !== Booking::STATUS_ACTIVE && $status !== Booking::STATUS_RETURN_PENDING) {
            throw new \InvalidArgumentException('Only active rentals can be returned.');
        }

        if ($status === Booking::STATUS_ACTIVE) {
            $booking = DB::transaction(function () use ($booking, $actor) {
                $old = $booking->status;
                $booking->update(['status' => Booking::STATUS_RETURN_PENDING]);
                $this->audit($actor, $booking, 'return_started', $old, Booking::STATUS_RETURN_PENDING);

                return $booking->fresh();
            });
        }

        return $this->completeBooking($booking, $actor, $data);
    }

    public function completeBooking(Booking $booking, User $actor, array $data = []): Booking
    {
        $this->assertBranchAccess($actor, $booking);

        $status = $booking->normalizeStatus();
        if (!in_array($status, [Booking::STATUS_ACTIVE, Booking::STATUS_RETURN_PENDING], true)) {
            throw new \InvalidArgumentException('Only active or return-pending rentals can be completed.');
        }

        if (!isset($data['return_mileage']) && !$booking->return_mileage) {
            throw new \InvalidArgumentException('Return mileage is required to complete the return.');
        }

        $requiresMaintenance = (bool) ($data['requires_maintenance'] ?? false);
        if (!empty($data['new_damage']) || !empty($data['damage_notes'])) {
            $requiresMaintenance = true;
        }

        return DB::transaction(function () use ($booking, $actor, $data, $requiresMaintenance) {
            $old = $booking->status;
            $additional = (float) ($data['additional_charges'] ?? 0);

            $booking->update([
                'status' => Booking::STATUS_COMPLETED,
                'returned_by' => $actor->id,
                'returned_at' => now(),
                'return_mileage' => $data['return_mileage'] ?? $booking->return_mileage,
                'return_fuel_level' => $data['return_fuel_level'] ?? $booking->return_fuel_level,
                'return_condition_notes' => $data['damage_notes'] ?? $data['new_damage'] ?? $booking->return_condition_notes,
                'requires_maintenance' => $requiresMaintenance,
                'additional_charges' => (float) $booking->additional_charges + $additional,
                'total_price' => (float) $booking->total_price + $additional,
                'notes' => $this->appendNote($booking->notes, 'RETURN', array_merge($data, [
                    'returned_by' => $actor->id,
                    'returned_at' => now()->toIso8601String(),
                ])),
            ]);

            $vehicleStatus = $requiresMaintenance
                ? Vehicle::STATUS_MAINTENANCE
                : Vehicle::STATUS_RETURN_PENDING_INSPECTION;

            $vehicleUpdate = ['status' => $vehicleStatus];
            if (isset($data['return_mileage'])) {
                $vehicleUpdate['mileage'] = (int) $data['return_mileage'];
            }
            $booking->vehicle->update($vehicleUpdate);

            if (!$requiresMaintenance) {
                $this->inspectionService->createPostReturnInspection($booking->fresh(), $actor);
            }

            if ($requiresMaintenance && (!empty($data['new_damage']) || !empty($data['damage_notes']))) {
                app(VehicleDamageService::class)->create([
                    'vehicle_id' => $booking->vehicle_id,
                    'booking_id' => $booking->id,
                    'damage_type' => 'return_damage',
                    'description' => $data['damage_notes'] ?? $data['new_damage'] ?? 'Damage reported on return.',
                    'severity' => $data['damage_severity'] ?? 'medium',
                    'repair_status' => 'pending',
                ], $actor);
            }

            $fresh = $booking->fresh()->load(['vehicle', 'user', 'branch', 'payments']);
            $this->audit($actor, $fresh, 'booking_completed', $old, $fresh->status, 'Vehicle returned and booking completed');
            event(new BookingCompleted($fresh));

            return $fresh;
        });
    }

    public function cancelBooking(Booking $booking, ?User $actor = null, ?string $reason = null, string $source = 'system'): Booking
    {
        $status = $booking->normalizeStatus();
        if (!in_array($status, Booking::CANCELLABLE_STATUSES, true)) {
            throw new \InvalidArgumentException('This booking cannot be cancelled in its current state.');
        }

        return DB::transaction(function () use ($booking, $actor, $reason, $source) {
            $old = $booking->status;

            $booking->update([
                'status' => Booking::STATUS_CANCELLED,
                'cancelled_by' => $actor?->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancellation_source' => $source,
            ]);

            // Fail only unpaid attempts — keep PAID until refund processed
            $booking->payments()
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING, Payment::STATUS_CASH_PENDING])
                ->update([
                    'status' => Payment::STATUS_CANCELLED,
                    'failure_reason' => 'Booking cancelled',
                ]);

            if (in_array($booking->vehicle->status, [Vehicle::STATUS_RESERVED], true)) {
                $booking->vehicle->update(['status' => Vehicle::STATUS_AVAILABLE]);
            }

            $fresh = $booking->fresh()->load(['vehicle', 'user', 'branch', 'payments']);
            $this->audit($actor, $fresh, 'booking_cancelled', $old, $fresh->status, $reason);
            event(new BookingCancelled($fresh));

            return $fresh;
        });
    }

    public function adminOverride(Booking $booking, User $admin, string $newStatus, string $reason): Booking
    {
        if (!$admin->isAdmin()) {
            throw new \InvalidArgumentException('Only administrators can override booking workflow.');
        }

        if (strlen(trim($reason)) < 5) {
            throw new \InvalidArgumentException('An override reason is required.');
        }

        if (!in_array($newStatus, Booking::STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid target status for override.');
        }

        return DB::transaction(function () use ($booking, $admin, $newStatus, $reason) {
            $old = $booking->status;
            $booking->update([
                'status' => $newStatus,
                'override_by' => $admin->id,
                'override_at' => now(),
                'override_reason' => $reason,
            ]);

            $fresh = $booking->fresh()->load(['vehicle', 'user', 'branch', 'payments']);
            $this->audit($admin, $fresh, 'admin_override', $old, $newStatus, $reason);

            return $fresh;
        });
    }

    /**
     * Compute allowed actions for the authenticated actor.
     *
     * @return list<string>
     */
    public function allowedActions(Booking $booking, ?User $actor = null): array
    {
        $actions = ['view'];
        if (!$actor) {
            return $actions;
        }

        $status = $booking->normalizeStatus();
        $sameBranch = $actor->isAdmin() || (int) $actor->branch_id === (int) $booking->branch_id;
        $paid = $booking->isPaymentSatisfied();
        $isTerminal = in_array($status, [
            Booking::STATUS_CANCELLED,
            Booking::STATUS_REJECTED,
            Booking::STATUS_EXPIRED,
            Booking::STATUS_COMPLETED,
        ], true);

        if ($actor->id === $booking->user_id && in_array($status, Booking::CANCELLABLE_STATUSES, true)) {
            $actions[] = 'cancel';
        }

        // Payment is a separate workflow gate from cancellation:
        // if branch/admin approvals are satisfied and booking is payable, show PAY NOW.
        if ($actor->id === $booking->user_id
            && !$isTerminal
            && !$paid
            && $booking->payment_status !== Booking::PAYMENT_STATUS_CASH_PENDING
            && $booking->payment_status !== Booking::PAYMENT_STATUS_NOT_REQUIRED
            && $booking->isBranchApproved()
            && $booking->isAdminApproved()
            && in_array($status, [
                Booking::STATUS_PAYMENT_REQUIRED,
                Booking::STATUS_PENDING_PAYMENT,
                Booking::STATUS_PENDING,
                Booking::STATUS_PAYMENT_VERIFIED,
            ], true)) {
            $actions[] = 'pay';
        }

        if ($status === Booking::STATUS_COMPLETED && $actor->id === $booking->user_id && !$booking->review) {
            $actions[] = 'write_review';
        }

        if ($booking->review) {
            $actions[] = 'view_review';
        }

        if ($paid) {
            $actions[] = 'view_payment';
        }

        if (!$sameBranch && !$actor->isAdmin()) {
            return array_values(array_unique($actions));
        }

        if ($status === Booking::STATUS_PENDING_BRANCH_APPROVAL
            && $booking->branch_approval_status === Booking::APPROVAL_PENDING
            && !$paid
            && ($actor->isAdmin() || $actor->isBranchManager())) {
            $actions[] = 'approve_branch';
            $actions[] = 'reject_branch';
        }

        if ($status === Booking::STATUS_PENDING_ADMIN_APPROVAL
            && $booking->admin_approval_status === Booking::APPROVAL_PENDING
            && !$paid
            && $actor->isAdmin()) {
            $actions[] = 'approve_admin';
            $actions[] = 'reject_admin';
        }

        if ($status === Booking::STATUS_CONFIRMED
            && $this->assertReadyForOperationalActionsQuiet($booking)
            && ($actor->isAdmin() || $actor->isBranchManager() || $actor->isStaff())) {
            $actions[] = 'prepare_pickup';
        }

        if (in_array($status, [Booking::STATUS_READY_FOR_PICKUP], true)
            || ($status === Booking::STATUS_CONFIRMED && $this->isPickupTimingSatisfied($booking))) {
            if ($this->assertReadyForOperationalActionsQuiet($booking)
                && ($actor->isAdmin() || $actor->isBranchManager() || $actor->isStaff())) {
                $actions[] = 'mark_picked_up';
            }
        }

        if ($status === Booking::STATUS_ACTIVE
            && ($actor->isAdmin() || $actor->isBranchManager() || $actor->isStaff())) {
            $actions[] = 'mark_returned';
        }

        if ($status === Booking::STATUS_RETURN_PENDING
            && ($actor->isAdmin() || $actor->isBranchManager() || $actor->isStaff())) {
            $actions[] = 'complete_return';
        }

        $cashPending = $booking->payments
            ? $booking->payments->firstWhere('status', Payment::STATUS_CASH_PENDING)
            : $booking->payments()->where('status', Payment::STATUS_CASH_PENDING)->first();

        if ($cashPending && ($actor->isAdmin() || $actor->isBranchManager() || $actor->isStaff())) {
            $actions[] = 'confirm_cash';
        }

        if (in_array($status, [Booking::STATUS_COMPLETED, Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED, Booking::STATUS_EXPIRED], true)
            && $actor->isAdmin()) {
            $actions[] = 'archive';
        }

        if ($actor->isAdmin()) {
            $actions[] = 'admin_override';
        }

        return array_values(array_unique($actions));
    }

    /**
     * Customer-facing timeline steps.
     */
    public function buildTimeline(Booking $booking): array
    {
        $status = $booking->normalizeStatus();
        $paid = $booking->isPaymentSatisfied() || $booking->payment_status === Booking::PAYMENT_STATUS_PAID;
        $cashPending = $booking->payment_status === Booking::PAYMENT_STATUS_CASH_PENDING;
        $branchDone = in_array($booking->branch_approval_status, [
            Booking::APPROVAL_APPROVED,
            Booking::APPROVAL_NOT_REQUIRED,
        ], true);

        $steps = [
            [
                'key' => 'created',
                'label' => 'Booking Created',
                'state' => 'done',
                'at' => $booking->created_at?->toISOString(),
            ],
            [
                'key' => 'branch',
                'label' => 'Branch Confirmation',
                'state' => match ($booking->branch_approval_status) {
                    Booking::APPROVAL_APPROVED, Booking::APPROVAL_NOT_REQUIRED => 'done',
                    Booking::APPROVAL_REJECTED => 'rejected',
                    default => ($status === Booking::STATUS_PENDING_BRANCH_APPROVAL ? 'current' : 'pending'),
                },
                'detail' => match ($booking->branch_approval_status) {
                    Booking::APPROVAL_APPROVED => 'Approved',
                    Booking::APPROVAL_NOT_REQUIRED => 'Approved',
                    Booking::APPROVAL_REJECTED => 'Rejected',
                    default => 'Waiting for branch approval',
                },
                'at' => $booking->branch_approved_at?->toISOString(),
            ],
            [
                'key' => 'admin',
                'label' => 'Admin Confirmation',
                'state' => match (true) {
                    !$booking->admin_approval_required || $booking->admin_approval_status === Booking::APPROVAL_NOT_REQUIRED => 'skipped',
                    $booking->admin_approval_status === Booking::APPROVAL_APPROVED => 'done',
                    $booking->admin_approval_status === Booking::APPROVAL_REJECTED => 'rejected',
                    $status === Booking::STATUS_PENDING_ADMIN_APPROVAL => 'current',
                    default => 'pending',
                },
                'detail' => match (true) {
                    !$booking->admin_approval_required || $booking->admin_approval_status === Booking::APPROVAL_NOT_REQUIRED => 'Not required',
                    $booking->admin_approval_status === Booking::APPROVAL_APPROVED => 'Approved',
                    $booking->admin_approval_status === Booking::APPROVAL_REJECTED => 'Rejected',
                    default => 'Waiting',
                },
                'at' => $booking->admin_approved_at?->toISOString(),
            ],
            [
                'key' => 'payment',
                'label' => 'Payment',
                'state' => $paid ? 'done' : match (true) {
                    $cashPending => 'current',
                    in_array($status, [Booking::STATUS_PAYMENT_REQUIRED, Booking::STATUS_PAYMENT_PROCESSING], true) => 'current',
                    $branchDone && $status !== Booking::STATUS_PENDING_BRANCH_APPROVAL => 'current',
                    default => 'pending',
                },
                'detail' => $paid ? 'Paid & verified' : match (true) {
                    $cashPending => 'Awaiting cash confirmation',
                    $booking->payment_status === Booking::PAYMENT_STATUS_NOT_REQUIRED => 'Not required yet',
                    in_array($status, [Booking::STATUS_PAYMENT_REQUIRED, Booking::STATUS_PAYMENT_PROCESSING], true) => 'Payment required',
                    default => 'Awaiting branch approval',
                },
                'at' => $booking->payments->firstWhere('status', Payment::STATUS_PAID)?->paid_at?->toISOString(),
            ],
            [
                'key' => 'confirmed',
                'label' => 'Booking Confirmation',
                'state' => in_array($status, [
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_READY_FOR_PICKUP,
                    Booking::STATUS_ACTIVE,
                    Booking::STATUS_RETURN_PENDING,
                    Booking::STATUS_COMPLETED,
                ], true) ? 'done' : 'pending',
                'detail' => in_array($status, [Booking::STATUS_CONFIRMED, Booking::STATUS_READY_FOR_PICKUP, Booking::STATUS_ACTIVE, Booking::STATUS_COMPLETED, Booking::STATUS_RETURN_PENDING], true)
                    ? 'Confirmed'
                    : 'Not yet available',
            ],
            [
                'key' => 'pickup',
                'label' => 'Pickup',
                'state' => match ($status) {
                    Booking::STATUS_READY_FOR_PICKUP => 'current',
                    Booking::STATUS_ACTIVE, Booking::STATUS_RETURN_PENDING, Booking::STATUS_COMPLETED => 'done',
                    default => 'pending',
                },
                'detail' => match ($status) {
                    Booking::STATUS_READY_FOR_PICKUP => 'Ready for pickup',
                    Booking::STATUS_ACTIVE, Booking::STATUS_RETURN_PENDING, Booking::STATUS_COMPLETED => 'Picked up',
                    Booking::STATUS_CONFIRMED => 'Upcoming',
                    default => 'Not yet available',
                },
                'at' => $booking->picked_up_at?->toISOString(),
            ],
            [
                'key' => 'active',
                'label' => 'Rental Active',
                'state' => match ($status) {
                    Booking::STATUS_ACTIVE => 'current',
                    Booking::STATUS_RETURN_PENDING, Booking::STATUS_COMPLETED => 'done',
                    default => 'pending',
                },
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'state' => $status === Booking::STATUS_COMPLETED ? 'done' : ($status === Booking::STATUS_RETURN_PENDING ? 'current' : 'pending'),
                'at' => $booking->returned_at?->toISOString(),
            ],
        ];

        if (in_array($status, [Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED], true)) {
            $steps[] = [
                'key' => 'terminal',
                'label' => $status === Booking::STATUS_CANCELLED ? 'Cancelled' : 'Rejected',
                'state' => 'rejected',
                'detail' => $booking->rejection_reason ?? $booking->cancellation_reason,
                'at' => ($booking->rejected_at ?? $booking->cancelled_at)?->toISOString(),
            ];
        }

        return $steps;
    }

    public function summaryCounts(?User $user = null, ?int $branchId = null): array
    {
        $query = Booking::query()->activeRecords();

        if ($user && !$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $base = clone $query;

        return [
            'pending_branch_approval' => (clone $base)->where('status', Booking::STATUS_PENDING_BRANCH_APPROVAL)->count(),
            'payment_required' => (clone $base)->where('status', Booking::STATUS_PAYMENT_REQUIRED)->count(),
            'payment_processing' => (clone $base)->where('status', Booking::STATUS_PAYMENT_PROCESSING)->count(),
            'pending_payment' => (clone $base)->whereIn('status', [Booking::STATUS_PENDING_PAYMENT, Booking::STATUS_PENDING])->count(),
            'awaiting_branch_approval' => (clone $base)->where('status', Booking::STATUS_PENDING_BRANCH_APPROVAL)->count(),
            'awaiting_admin_approval' => (clone $base)->where('status', Booking::STATUS_PENDING_ADMIN_APPROVAL)->count(),
            'confirmed' => (clone $base)->where('status', Booking::STATUS_CONFIRMED)->count(),
            'ready_for_pickup' => (clone $base)->where('status', Booking::STATUS_READY_FOR_PICKUP)->count(),
            'active' => (clone $base)->where('status', Booking::STATUS_ACTIVE)->count(),
            'return_pending' => (clone $base)->where('status', Booking::STATUS_RETURN_PENDING)->count(),
            'completed' => (clone $base)->where('status', Booking::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $base)->where('status', Booking::STATUS_CANCELLED)->count(),
            'rejected' => (clone $base)->where('status', Booking::STATUS_REJECTED)->count(),
            'total' => (clone $base)->count(),
        ];
    }

    // ─── Internals ────────────────────────────────────────────────────

    private function performRejection(Booking $booking, User $rejector, string $role, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $rejector, $role, $reason) {
            $old = $booking->status;
            $updates = [
                'status' => Booking::STATUS_REJECTED,
                'payment_status' => Booking::PAYMENT_STATUS_NOT_REQUIRED,
                'rejected_by_role' => $role,
                'rejected_by_user_id' => $rejector->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'notes' => $this->appendNote($booking->notes, 'REJECTION', ['reason' => $reason, 'role' => $role]),
            ];

            if ($role === 'branch') {
                $updates['branch_approval_status'] = Booking::APPROVAL_REJECTED;
            } else {
                $updates['admin_approval_status'] = Booking::APPROVAL_REJECTED;
            }

            $booking->update($updates);

            if (in_array($booking->vehicle->status, [Vehicle::STATUS_RESERVED], true)) {
                $booking->vehicle->update(['status' => Vehicle::STATUS_AVAILABLE]);
            }

            $fresh = $booking->fresh()->load(['vehicle', 'user', 'branch', 'payments']);
            $this->audit($rejector, $fresh, $role === 'branch' ? 'branch_rejected' : 'admin_rejected', $old, $fresh->status, $reason);
            event(new BookingRejected($fresh, $reason));

            return $fresh;
        });
    }

    private function maybeMarkReadyForPickup(Booking $booking): void
    {
        $hours = (int) config('booking.ready_for_pickup_hours_before', 24);
        $pickup = $booking->pickup_date instanceof Carbon
            ? $booking->pickup_date
            : Carbon::parse($booking->pickup_date);

        if ($hours <= 0 || $pickup->lte(now()->addHours($hours))) {
            $booking->update(['status' => Booking::STATUS_READY_FOR_PICKUP]);
        }
    }

    private function isPickupTimingSatisfied(Booking $booking): bool
    {
        if ($booking->normalizeStatus() === Booking::STATUS_READY_FOR_PICKUP) {
            return true;
        }

        $hours = (int) config('booking.ready_for_pickup_hours_before', 24);
        $pickup = $booking->pickup_date instanceof Carbon
            ? $booking->pickup_date
            : Carbon::parse($booking->pickup_date);

        return $pickup->lte(now()->addHours(max($hours, 0)));
    }

    private function assertReadyForOperationalActions(Booking $booking): void
    {
        if (!$booking->isPaymentSatisfied()) {
            throw new \InvalidArgumentException('Booking cannot proceed because payment is not verified.');
        }
        if (!$booking->isBranchApproved()) {
            throw new \InvalidArgumentException('Booking cannot be picked up because branch approval is still pending.');
        }
        if (!$booking->isAdminApproved()) {
            throw new \InvalidArgumentException('Booking cannot be picked up because admin approval is still pending.');
        }
        if (in_array($booking->normalizeStatus(), [Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED], true)) {
            throw new \InvalidArgumentException('Cancelled or rejected bookings cannot proceed to pickup.');
        }
        if ($booking->vehicle && in_array($booking->vehicle->status, [Vehicle::STATUS_MAINTENANCE, Vehicle::STATUS_UNAVAILABLE], true)) {
            throw new \InvalidArgumentException('Vehicle is unavailable for pickup.');
        }
    }

    private function assertReadyForOperationalActionsQuiet(Booking $booking): bool
    {
        try {
            $this->assertReadyForOperationalActions($booking);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    private function assertVehicleAvailableForConfirmation(Booking $booking): void
    {
        $vehicle = $booking->vehicle;
        if (!$vehicle) {
            throw new \InvalidArgumentException('Booking vehicle not found.');
        }

        if ((int) $vehicle->branch_id !== (int) $booking->branch_id) {
            throw new \InvalidArgumentException('Vehicle branch does not match booking branch.');
        }

        if ($vehicle->branch && !$vehicle->branch->isActive()) {
            throw new \InvalidArgumentException('Booking branch is inactive.');
        }

        if (in_array($vehicle->status, [Vehicle::STATUS_MAINTENANCE, Vehicle::STATUS_UNAVAILABLE], true)) {
            throw new \InvalidArgumentException('Vehicle is not available for confirmation.');
        }

        $overlap = Booking::overlapping(
            $booking->vehicle_id,
            $booking->pickup_date,
            $booking->return_date
        )->where('id', '!=', $booking->id)
            ->whereIn('status', [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_READY_FOR_PICKUP,
                Booking::STATUS_ACTIVE,
                Booking::STATUS_RETURN_PENDING,
            ])
            ->exists();

        if ($overlap) {
            throw new \InvalidArgumentException('Vehicle has another confirmed or active booking for overlapping dates.');
        }
    }

    private function assertNotTerminal(Booking $booking): void
    {
        if (in_array($booking->normalizeStatus(), [
            Booking::STATUS_CANCELLED,
            Booking::STATUS_REJECTED,
            Booking::STATUS_EXPIRED,
            Booking::STATUS_COMPLETED,
        ], true)) {
            throw new \InvalidArgumentException('Booking is in a terminal state.');
        }
    }

    private function assertCanApproveBranch(Booking $booking, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if (!$user->isBranchManager()) {
            throw new \InvalidArgumentException('You are not authorized to approve branch bookings.');
        }

        if ((int) $user->branch_id !== (int) $booking->branch_id) {
            throw new \InvalidArgumentException('You are not authorized to manage bookings for this branch.');
        }
    }

    private function assertBranchAccess(User $user, Booking $booking): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if (!$user->isBranchManager() && !$user->isStaff()) {
            throw new \InvalidArgumentException('You are not authorized to manage this booking.');
        }

        if ((int) $user->branch_id !== (int) $booking->branch_id) {
            throw new \InvalidArgumentException('You are not authorized to manage bookings for this branch.');
        }
    }

    private function appendNote(?string $existing, string $label, array $meta): string
    {
        $line = "[{$label}] " . json_encode($meta);

        return $existing ? $existing . "\n" . $line : $line;
    }

    private function audit(?User $actor, Booking $booking, string $action, ?string $oldStatus, ?string $newStatus, ?string $notes = null): void
    {
        if (!$actor) {
            return;
        }

        $this->auditLogService->log(
            $actor,
            $action,
            'booking',
            $booking->id,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $notes,
            $booking->branch_id
        );
    }
}
