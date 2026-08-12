<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDamage;
use App\Models\VehicleTransfer;
use App\Notifications\VehicleTransferNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class VehicleTransferService
{
    public const ACTIVE_STATUSES = [
        VehicleTransfer::STATUS_PENDING,
        'requested',
        VehicleTransfer::STATUS_APPROVED,
        VehicleTransfer::STATUS_READY_FOR_RELEASE,
        VehicleTransfer::STATUS_IN_TRANSIT,
        VehicleTransfer::STATUS_RECEIVED,
        VehicleTransfer::STATUS_RECEIVED_PENDING_INSPECTION,
    ];

    public function __construct(
        private AuditLogService $auditLog
    ) {}

    public function activeStatuses(): array
    {
        return self::ACTIVE_STATUSES;
    }

    public function reference(VehicleTransfer $transfer): string
    {
        return 'TR-' . str_pad((string) $transfer->id, 4, '0', STR_PAD_LEFT);
    }

    public function createRequest(array $data, User $actor): VehicleTransfer
    {
        $vehicle = Vehicle::query()->with(['branch'])->findOrFail($data['vehicle_id']);
        $toBranchId = (int) $data['to_branch_id'];
        $fromBranchId = (int) $vehicle->branch_id;
        $transferDate = Carbon::parse($data['transfer_date'])->toDateString();

        $this->assertCanRequestFromBranch($actor, $vehicle);
        $this->assertDistinctBranches($fromBranchId, $toBranchId);
        $this->assertDestinationActive($toBranchId);
        $this->validateVehicleEligible($vehicle, $fromBranchId, $transferDate);

        $transfer = null;

        DB::transaction(function () use (&$transfer, $actor, $vehicle, $fromBranchId, $toBranchId, $transferDate, $data) {
            $vehicleLocked = Vehicle::query()->where('id', $vehicle->id)->lockForUpdate()->firstOrFail();
            $this->validateVehicleEligible($vehicleLocked, $fromBranchId, $transferDate);

            if ($this->hasActiveTransfer($vehicleLocked->id)) {
                throw new \RuntimeException('Vehicle already has an active transfer.');
            }

            $transfer = VehicleTransfer::create([
                'vehicle_id' => $vehicleLocked->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'requested_by' => $actor->id,
                'transfer_date' => $transferDate,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'request_notes' => $data['notes'] ?? null,
                'status' => VehicleTransfer::STATUS_PENDING,
                'requested_at' => now(),
            ]);

            $this->audit($actor, 'transfer_requested', $transfer, null, VehicleTransfer::STATUS_PENDING, 'Transfer request created.', $fromBranchId);
        });

        $this->notifyFleetManagers($transfer->fresh(['vehicle', 'fromBranch', 'toBranch', 'requester']), 'transfer_requested', 'New Transfer Request', sprintf(
            'Transfer %s requested for %s %s from %s to %s.',
            $this->reference($transfer),
            $transfer->vehicle?->brand,
            $transfer->vehicle?->model,
            $transfer->fromBranch?->name,
            $transfer->toBranch?->name
        ));

        return $transfer->load(['vehicle', 'fromBranch', 'toBranch', 'requester']);
    }

    public function approve(VehicleTransfer $transfer, User $actor, ?string $approvalNotes = null): VehicleTransfer
    {
        $this->assertFleetOrAdmin($actor);
        $this->assertStatus($transfer, [VehicleTransfer::STATUS_PENDING, 'requested'], 'Only pending transfers can be approved.');

        DB::transaction(function () use ($transfer, $actor, $approvalNotes) {
            $transferLocked = $this->lockTransfer($transfer);
            $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

            $this->assertStatus($transferLocked, [VehicleTransfer::STATUS_PENDING, 'requested'], 'Transfer cannot be approved from its current status.');
            $this->validateVehicleEligible($vehicleLocked, (int) $transferLocked->from_branch_id, $transferLocked->transfer_date->toDateString());

            if ($this->hasActiveTransfer($vehicleLocked->id, $transferLocked->id)) {
                throw new \RuntimeException('Vehicle already has an active transfer.');
            }

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_READY_FOR_RELEASE,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'approval_notes' => $approvalNotes,
            ]);

            $vehicleLocked->update(['status' => Vehicle::STATUS_TRANSFER_PENDING]);

            $this->audit($actor, 'transfer_approved', $transferLocked, VehicleTransfer::STATUS_PENDING, VehicleTransfer::STATUS_READY_FOR_RELEASE, 'Transfer approved and ready for release.', $transferLocked->from_branch_id);
        });

        $fresh = $transfer->fresh(['vehicle', 'fromBranch', 'toBranch', 'requester', 'approver']);
        $this->notifyBranchManagers($fresh->from_branch_id, $fresh, 'transfer_approved', 'Transfer Approved', sprintf(
            'Transfer %s for %s %s has been approved by the Fleet Manager.',
            $this->reference($fresh),
            $fresh->vehicle?->brand,
            $fresh->vehicle?->model
        ));

        return $fresh;
    }

    public function reject(VehicleTransfer $transfer, User $actor, string $reason): VehicleTransfer
    {
        $this->assertFleetOrAdmin($actor);
        $this->assertStatus($transfer, [VehicleTransfer::STATUS_PENDING, 'requested'], 'Only pending transfers can be rejected.');

        DB::transaction(function () use ($transfer, $actor, $reason) {
            $transferLocked = $this->lockTransfer($transfer);
            $this->assertStatus($transferLocked, [VehicleTransfer::STATUS_PENDING, 'requested'], 'Transfer cannot be rejected from its current status.');

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_REJECTED,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->audit($actor, 'transfer_rejected', $transferLocked, VehicleTransfer::STATUS_PENDING, VehicleTransfer::STATUS_REJECTED, $reason, $transferLocked->from_branch_id);
        });

        $fresh = $transfer->fresh(['vehicle', 'fromBranch', 'toBranch', 'requester']);
        $this->notifyBranchManagers($fresh->from_branch_id, $fresh, 'transfer_rejected', 'Transfer Rejected', sprintf(
            'Transfer %s was rejected. Reason: %s',
            $this->reference($fresh),
            $reason
        ));

        return $fresh;
    }

    public function cancel(VehicleTransfer $transfer, User $actor, ?string $reason = null): VehicleTransfer
    {
        if (!in_array($transfer->status, [
            VehicleTransfer::STATUS_PENDING,
            'requested',
            VehicleTransfer::STATUS_APPROVED,
            VehicleTransfer::STATUS_READY_FOR_RELEASE,
        ], true)) {
            throw new \RuntimeException('Only pending or approved transfers can be cancelled.');
        }

        if ($actor->isBranchManager()) {
            if ((int) $actor->branch_id !== (int) $transfer->from_branch_id) {
                throw new \RuntimeException('You are not authorized to cancel this transfer.');
            }
            if (!in_array($transfer->status, [VehicleTransfer::STATUS_PENDING, 'requested'], true)) {
                throw new \RuntimeException('Approved transfers can only be cancelled by Fleet Manager or Admin.');
            }
        } elseif (!$actor->isAdmin() && !$actor->isFleetManager()) {
            throw new \RuntimeException('You are not authorized to cancel this transfer.');
        }

        DB::transaction(function () use ($transfer, $actor, $reason) {
            $transferLocked = $this->lockTransfer($transfer);
            $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();
            $oldStatus = $transferLocked->status;

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_CANCELLED,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            if (in_array($oldStatus, [
                VehicleTransfer::STATUS_APPROVED,
                VehicleTransfer::STATUS_READY_FOR_RELEASE,
            ], true) && in_array($vehicleLocked->status, [
                Vehicle::STATUS_TRANSFER_PENDING,
                Vehicle::STATUS_UNAVAILABLE,
            ], true)) {
                $vehicleLocked->update(['status' => Vehicle::STATUS_AVAILABLE]);
            }

            $this->audit($actor, 'transfer_cancelled', $transferLocked, $oldStatus, VehicleTransfer::STATUS_CANCELLED, $reason, $transferLocked->from_branch_id);
        });

        return $transfer->fresh(['vehicle', 'fromBranch', 'toBranch']);
    }

    public function prepareRelease(VehicleTransfer $transfer, User $actor, array $data): VehicleTransfer
    {
        $this->assertSourceBranch($actor, $transfer);
        $this->assertStatus($transfer, [VehicleTransfer::STATUS_READY_FOR_RELEASE], 'Only transfers ready for release can be prepared.');

        $transfer->update([
            'source_odometer' => $data['source_odometer'] ?? $transfer->source_odometer,
            'source_fuel_level' => $data['source_fuel_level'] ?? $transfer->source_fuel_level,
            'source_condition' => $data['source_condition'] ?? $transfer->source_condition,
            'release_notes' => $data['release_notes'] ?? $transfer->release_notes,
        ]);

        return $transfer->fresh(['vehicle', 'fromBranch', 'toBranch']);
    }

    public function release(VehicleTransfer $transfer, User $actor, array $data): VehicleTransfer
    {
        $this->assertSourceBranch($actor, $transfer);
        $this->assertStatus($transfer, [VehicleTransfer::STATUS_READY_FOR_RELEASE], 'Only transfers ready for release can be released.');

        DB::transaction(function () use ($transfer, $actor, $data) {
            $transferLocked = $this->lockTransfer($transfer);
            $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

            $this->assertStatus($transferLocked, [VehicleTransfer::STATUS_READY_FOR_RELEASE], 'Transfer cannot be released from its current status.');
            $this->assertVehicleAtSourceBranch($vehicleLocked, $transferLocked);
            $this->validateVehicleEligible($vehicleLocked, (int) $transferLocked->from_branch_id, $transferLocked->transfer_date->toDateString());

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_IN_TRANSIT,
                'released_by' => $actor->id,
                'released_at' => now(),
                'in_transit_at' => now(),
                'started_by' => $actor->id,
                'source_odometer' => $data['source_odometer'] ?? $transferLocked->source_odometer,
                'source_fuel_level' => $data['source_fuel_level'] ?? $transferLocked->source_fuel_level,
                'source_condition' => $data['source_condition'] ?? $transferLocked->source_condition,
                'release_notes' => $data['release_notes'] ?? $transferLocked->release_notes,
            ]);

            // Vehicle remains at source branch until transfer completion.
            $vehicleLocked->update(['status' => Vehicle::STATUS_TRANSFER_IN_TRANSIT]);

            $this->audit($actor, 'transfer_released', $transferLocked, VehicleTransfer::STATUS_READY_FOR_RELEASE, VehicleTransfer::STATUS_IN_TRANSIT, 'Vehicle released for transit.', $transferLocked->from_branch_id);
        });

        $fresh = $transfer->fresh(['vehicle', 'fromBranch', 'toBranch']);
        $this->notifyBranchManagers($fresh->to_branch_id, $fresh, 'transfer_in_transit', 'Vehicle In Transit', sprintf(
            '%s %s is in transit from %s and is expected on %s.',
            $fresh->vehicle?->brand,
            $fresh->vehicle?->model,
            $fresh->fromBranch?->name,
            $fresh->transfer_date?->format('M j, Y')
        ));

        return $fresh;
    }

    public function receive(VehicleTransfer $transfer, User $actor, array $data): VehicleTransfer
    {
        $this->assertDestinationBranch($actor, $transfer);
        $this->assertStatus($transfer, [VehicleTransfer::STATUS_IN_TRANSIT], 'Only in-transit transfers can be received.');

        $hasDamage = !empty($data['damage_report']) || !empty($data['has_damage']);

        DB::transaction(function () use ($transfer, $actor, $data, $hasDamage) {
            $transferLocked = $this->lockTransfer($transfer);
            $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

            $this->assertStatus($transferLocked, [VehicleTransfer::STATUS_IN_TRANSIT], 'Transfer cannot be received from its current status.');
            $this->assertVehicleAtSourceBranch($vehicleLocked, $transferLocked);

            $nextStatus = $hasDamage
                ? VehicleTransfer::STATUS_RECEIVED_PENDING_INSPECTION
                : VehicleTransfer::STATUS_RECEIVED;

            $transferLocked->update([
                'status' => $nextStatus,
                'received_by' => $actor->id,
                'received_at' => now(),
                'destination_odometer' => $data['destination_odometer'] ?? null,
                'destination_fuel_level' => $data['destination_fuel_level'] ?? null,
                'destination_condition' => $data['destination_condition'] ?? null,
                'receiving_notes' => $data['receiving_notes'] ?? null,
                'damage_report' => $data['damage_report'] ?? null,
            ]);

            if ($hasDamage) {
                VehicleDamage::create([
                    'vehicle_id' => $vehicleLocked->id,
                    'reported_by' => $actor->id,
                    'damage_type' => 'transfer',
                    'description' => $data['damage_report'] ?? 'Damage reported during transfer receiving inspection.',
                    'severity' => $data['damage_severity'] ?? VehicleDamage::SEVERITY_MEDIUM,
                    'location' => $data['damage_location'] ?? 'unspecified',
                    'repair_status' => VehicleDamage::REPAIR_PENDING,
                    'reported_at' => now(),
                    'notes' => $data['receiving_notes'] ?? null,
                ]);

                $vehicleLocked->update(['status' => Vehicle::STATUS_INSPECTION_REQUIRED]);
                $this->audit($actor, 'transfer_damage_reported', $transferLocked, VehicleTransfer::STATUS_IN_TRANSIT, $nextStatus, $data['damage_report'] ?? null, $transferLocked->to_branch_id);
                return;
            }

            $this->audit($actor, 'transfer_received', $transferLocked, VehicleTransfer::STATUS_IN_TRANSIT, $nextStatus, 'Vehicle received at destination.', $transferLocked->to_branch_id);
        });

        $fresh = $transfer->fresh(['vehicle', 'fromBranch', 'toBranch']);

        if ($hasDamage) {
            $this->notifyFleetManagers($fresh, 'transfer_damage_reported', 'Transfer Damage Reported', sprintf(
                'Damage reported during transfer %s for %s %s.',
                $this->reference($fresh),
                $fresh->vehicle?->brand,
                $fresh->vehicle?->model
            ));
            $this->notifyBranchManagers($fresh->from_branch_id, $fresh, 'transfer_damage_reported', 'Transfer Damage Reported', 'Damage was reported during receiving inspection.');
            return $fresh;
        }

        return $this->complete($fresh, $actor);
    }

    public function complete(VehicleTransfer $transfer, User $actor): VehicleTransfer
    {
        if (!in_array($transfer->status, [
            VehicleTransfer::STATUS_RECEIVED,
            VehicleTransfer::STATUS_RECEIVED_PENDING_INSPECTION,
        ], true) && !$actor->isAdmin() && !$actor->isFleetManager()) {
            throw new \RuntimeException('Transfer cannot be completed from its current status.');
        }

        if (in_array($transfer->status, [VehicleTransfer::STATUS_RECEIVED_PENDING_INSPECTION], true)
            && !$actor->isAdmin() && !$actor->isFleetManager()) {
            throw new \RuntimeException('Transfers with pending damage inspection must be completed by Fleet Manager or Admin.');
        }

        DB::transaction(function () use ($transfer, $actor) {
            $transferLocked = $this->lockTransfer($transfer);
            $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

            if (!in_array($transferLocked->status, [
                VehicleTransfer::STATUS_RECEIVED,
                VehicleTransfer::STATUS_RECEIVED_PENDING_INSPECTION,
            ], true)) {
                throw new \RuntimeException('Transfer cannot be completed from its current status.');
            }

            $wasDamageHold = $transferLocked->status === VehicleTransfer::STATUS_RECEIVED_PENDING_INSPECTION;

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_COMPLETED,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            $vehicleLocked->update([
                'branch_id' => $transferLocked->to_branch_id,
                'status' => $wasDamageHold
                    ? Vehicle::STATUS_INSPECTION_REQUIRED
                    : Vehicle::STATUS_AVAILABLE,
            ]);

            $this->audit($actor, 'transfer_completed', $transferLocked, $transferLocked->getOriginal('status'), VehicleTransfer::STATUS_COMPLETED, 'Transfer completed. Vehicle assigned to destination branch.', $transferLocked->to_branch_id);
        });

        $fresh = $transfer->fresh(['vehicle', 'fromBranch', 'toBranch']);
        $this->notifyBranchManagers($fresh->from_branch_id, $fresh, 'transfer_completed', 'Transfer Completed', sprintf(
            '%s %s transfer to %s is complete.',
            $fresh->vehicle?->brand,
            $fresh->vehicle?->model,
            $fresh->toBranch?->name
        ));
        $this->notifyBranchManagers($fresh->to_branch_id, $fresh, 'transfer_completed', 'Transfer Completed', sprintf(
            '%s %s has been received at your branch.',
            $fresh->vehicle?->brand,
            $fresh->vehicle?->model
        ));
        $this->notifyFleetManagers($fresh, 'transfer_completed', 'Transfer Completed', sprintf(
            'Transfer %s completed successfully.',
            $this->reference($fresh)
        ));

        return $fresh;
    }

    public function markFailed(VehicleTransfer $transfer, User $actor, string $reason): VehicleTransfer
    {
        $this->assertFleetOrAdmin($actor);

        if ($transfer->status !== VehicleTransfer::STATUS_IN_TRANSIT) {
            throw new \RuntimeException('Only in-transit transfers can be marked as failed.');
        }

        DB::transaction(function () use ($transfer, $actor, $reason) {
            $transferLocked = $this->lockTransfer($transfer);
            $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_FAILED,
                'failed_by' => $actor->id,
                'failed_at' => now(),
                'failure_reason' => $reason,
            ]);

            // Keep vehicle at source branch; mark unavailable until fleet resolves.
            $vehicleLocked->update([
                'branch_id' => $transferLocked->from_branch_id,
                'status' => Vehicle::STATUS_UNAVAILABLE,
            ]);

            $this->audit($actor, 'transfer_failed', $transferLocked, VehicleTransfer::STATUS_IN_TRANSIT, VehicleTransfer::STATUS_FAILED, $reason, $transferLocked->from_branch_id);
        });

        $fresh = $transfer->fresh(['vehicle', 'fromBranch', 'toBranch']);
        $this->notifyBranchManagers($fresh->from_branch_id, $fresh, 'transfer_failed', 'Transfer Failed', $reason);
        $this->notifyBranchManagers($fresh->to_branch_id, $fresh, 'transfer_failed', 'Transfer Failed', $reason);

        return $fresh;
    }

    public function executeNow(VehicleTransfer $transfer, User $actor): VehicleTransfer
    {
        if (!$actor->isAdmin()) {
            throw new \RuntimeException('Only administrators can execute instant transfers.');
        }

        if (in_array($transfer->status, [
            VehicleTransfer::STATUS_COMPLETED,
            VehicleTransfer::STATUS_REJECTED,
            VehicleTransfer::STATUS_CANCELLED,
            VehicleTransfer::STATUS_FAILED,
        ], true)) {
            throw new \RuntimeException('This transfer is already finalized.');
        }

        DB::transaction(function () use ($transfer, $actor) {
            $transferLocked = $this->lockTransfer($transfer);
            $vehicleLocked = Vehicle::query()->where('id', $transferLocked->vehicle_id)->lockForUpdate()->firstOrFail();

            if (in_array($transferLocked->status, [
                VehicleTransfer::STATUS_COMPLETED,
                VehicleTransfer::STATUS_REJECTED,
                VehicleTransfer::STATUS_CANCELLED,
                VehicleTransfer::STATUS_FAILED,
            ], true)) {
                throw new \RuntimeException('This transfer is already finalized.');
            }

            if (in_array($transferLocked->status, [VehicleTransfer::STATUS_PENDING, 'requested'], true)) {
                $this->validateVehicleEligible($vehicleLocked, (int) $transferLocked->from_branch_id, $transferLocked->transfer_date->toDateString());
                $transferLocked->update([
                    'status' => VehicleTransfer::STATUS_READY_FOR_RELEASE,
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ]);
            }

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_IN_TRANSIT,
                'released_by' => $actor->id,
                'released_at' => now(),
                'in_transit_at' => now(),
                'started_by' => $actor->id,
            ]);

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_RECEIVED,
                'received_by' => $actor->id,
                'received_at' => now(),
            ]);

            $transferLocked->update([
                'status' => VehicleTransfer::STATUS_COMPLETED,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            $vehicleLocked->update([
                'branch_id' => $transferLocked->to_branch_id,
                'status' => Vehicle::STATUS_AVAILABLE,
            ]);

            $this->audit($actor, 'transfer_executed', $transferLocked, null, VehicleTransfer::STATUS_COMPLETED, 'Admin executed full transfer.', $transferLocked->to_branch_id);
        });

        return $transfer->fresh(['vehicle', 'fromBranch', 'toBranch']);
    }

    public function validateVehicleEligible(Vehicle $vehicle, int $fromBranchId, string $transferDate): void
    {
        if ((int) $vehicle->branch_id !== $fromBranchId) {
            throw new \RuntimeException('Vehicle does not belong to the source branch.');
        }

        if ($vehicle->status === Vehicle::STATUS_RENTED) {
            throw new \RuntimeException('Vehicle cannot be transferred while it is being rented.');
        }

        if ($vehicle->status === Vehicle::STATUS_MAINTENANCE) {
            throw new \RuntimeException('Vehicle cannot be transferred while under maintenance.');
        }

        if (!in_array($vehicle->status, [
            Vehicle::STATUS_AVAILABLE,
            Vehicle::STATUS_TRANSFER_PENDING,
            Vehicle::STATUS_TRANSFER_IN_TRANSIT,
        ], true)) {
            throw new \RuntimeException('Vehicle cannot be transferred because it is currently not available.');
        }

        if (Maintenance::query()->active()->where('vehicle_id', $vehicle->id)->exists()) {
            throw new \RuntimeException('Vehicle is currently under maintenance and cannot be transferred.');
        }

        if ($vehicle->hasExpiredRequiredDocuments()) {
            throw new \RuntimeException('Vehicle has expired required documents and cannot be transferred.');
        }

        if ($this->hasConflictingBooking($vehicle->id, $fromBranchId, $transferDate)) {
            throw new \RuntimeException('This vehicle has an upcoming confirmed booking and cannot be transferred.');
        }
    }

    public function hasActiveTransfer(int $vehicleId, ?int $exceptTransferId = null): bool
    {
        $query = VehicleTransfer::query()
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', self::ACTIVE_STATUSES);

        if ($exceptTransferId) {
            $query->where('id', '!=', $exceptTransferId);
        }

        return $query->exists();
    }

    public function hasConflictingBooking(int $vehicleId, int $branchId, string $transferDate): bool
    {
        $dayStart = Carbon::parse($transferDate)->startOfDay();
        $dayEnd = Carbon::parse($transferDate)->endOfDay();

        return Booking::query()
            ->where('vehicle_id', $vehicleId)
            ->where('branch_id', $branchId)
            ->whereIn('status', [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_READY_FOR_PICKUP,
                Booking::STATUS_ACTIVE,
            ])
            ->where(function ($q) use ($dayStart, $dayEnd) {
                $q->whereBetween('pickup_date', [$dayStart, $dayEnd])
                    ->orWhereBetween('return_date', [$dayStart, $dayEnd])
                    ->orWhere(function ($q2) use ($dayStart, $dayEnd) {
                        $q2->where('pickup_date', '<=', $dayStart)
                            ->where('return_date', '>=', $dayEnd);
                    });
            })
            ->exists();
    }

    private function assertCanRequestFromBranch(User $actor, Vehicle $vehicle): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        if (!$actor->isBranchManager() || (int) $vehicle->branch_id !== (int) $actor->branch_id) {
            throw new \RuntimeException('You can only transfer vehicles from your own branch.');
        }
    }

    private function assertDistinctBranches(int $fromBranchId, int $toBranchId): void
    {
        if ($fromBranchId === $toBranchId) {
            throw new \RuntimeException('Destination branch must be different from the current branch.');
        }
    }

    private function assertDestinationActive(int $toBranchId): void
    {
        $toBranch = Branch::findOrFail($toBranchId);
        if (!$toBranch->isActive()) {
            throw new \RuntimeException('Destination branch is inactive.');
        }
    }

    private function assertFleetOrAdmin(User $actor): void
    {
        if (!$actor->isAdmin() && !$actor->isFleetManager()) {
            throw new \RuntimeException('You are not authorized to perform this transfer action.');
        }
    }

    private function assertSourceBranch(User $actor, VehicleTransfer $transfer): void
    {
        if ($actor->isAdmin() || $actor->isFleetManager()) {
            return;
        }

        if ((int) $actor->branch_id !== (int) $transfer->from_branch_id) {
            throw new \RuntimeException('You are not authorized to release this transfer.');
        }
    }

    private function assertDestinationBranch(User $actor, VehicleTransfer $transfer): void
    {
        if ($actor->isAdmin() || $actor->isFleetManager()) {
            return;
        }

        if ((int) $actor->branch_id !== (int) $transfer->to_branch_id) {
            throw new \RuntimeException('Only the destination branch can confirm arrival.');
        }
    }

    private function assertVehicleAtSourceBranch(Vehicle $vehicle, VehicleTransfer $transfer): void
    {
        if ((int) $vehicle->branch_id !== (int) $transfer->from_branch_id) {
            throw new \RuntimeException('Vehicle must still belong to the source branch until transfer completion.');
        }
    }

    private function assertStatus(VehicleTransfer $transfer, array $allowed, string $message): void
    {
        if (!in_array($transfer->status, $allowed, true)) {
            throw new \RuntimeException($message);
        }
    }

    private function lockTransfer(VehicleTransfer $transfer): VehicleTransfer
    {
        return VehicleTransfer::query()->where('id', $transfer->id)->lockForUpdate()->firstOrFail();
    }

    private function audit(User $actor, string $action, VehicleTransfer $transfer, ?string $oldStatus, string $newStatus, ?string $notes, ?int $branchId): void
    {
        $this->auditLog->log(
            $actor,
            $action,
            'vehicle_transfer',
            $transfer->id,
            $oldStatus ? ['status' => $oldStatus] : null,
            ['status' => $newStatus],
            $notes,
            $branchId
        );
    }

    private function notifyFleetManagers(VehicleTransfer $transfer, string $type, string $title, string $message): void
    {
        $recipients = $this->fleetAndAdminRecipients();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new VehicleTransferNotification($transfer, $type, $title, $message));
        }
    }

    private function notifyBranchManagers(int $branchId, VehicleTransfer $transfer, string $type, string $title, string $message): void
    {
        $managers = User::query()
            ->where('role', User::ROLE_BRANCH_MANAGER)
            ->where('branch_id', $branchId)
            ->get();

        if ($managers->isNotEmpty()) {
            Notification::send($managers, new VehicleTransferNotification($transfer, $type, $title, $message));
        }
    }

    private function fleetAndAdminRecipients(): Collection
    {
        return User::query()
            ->whereIn('role', [User::ROLE_FLEET_MANAGER, User::ROLE_COMPANY_ADMIN, User::ROLE_SUPER_ADMIN])
            ->get();
    }
}
