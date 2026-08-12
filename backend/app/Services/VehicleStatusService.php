<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use InvalidArgumentException;

class VehicleStatusService
{
    /** Allowed manual/admin transitions (from => [to, ...]) */
    private const TRANSITIONS = [
        Vehicle::STATUS_AVAILABLE => [
            Vehicle::STATUS_RESERVED,
            Vehicle::STATUS_READY_FOR_PICKUP,
            Vehicle::STATUS_MAINTENANCE,
            Vehicle::STATUS_UNAVAILABLE,
            Vehicle::STATUS_TRANSFER_PENDING,
            Vehicle::STATUS_RETIRED,
        ],
        Vehicle::STATUS_RESERVED => [
            Vehicle::STATUS_AVAILABLE,
            Vehicle::STATUS_READY_FOR_PICKUP,
            Vehicle::STATUS_RENTED,
            Vehicle::STATUS_MAINTENANCE,
            Vehicle::STATUS_UNAVAILABLE,
        ],
        Vehicle::STATUS_READY_FOR_PICKUP => [
            Vehicle::STATUS_RENTED,
            Vehicle::STATUS_AVAILABLE,
            Vehicle::STATUS_RESERVED,
        ],
        Vehicle::STATUS_RENTED => [
            Vehicle::STATUS_RETURN_PENDING_INSPECTION,
            Vehicle::STATUS_MAINTENANCE,
        ],
        Vehicle::STATUS_RETURN_PENDING_INSPECTION => [
            Vehicle::STATUS_AVAILABLE,
            Vehicle::STATUS_INSPECTION_REQUIRED,
            Vehicle::STATUS_MAINTENANCE,
        ],
        Vehicle::STATUS_INSPECTION_REQUIRED => [
            Vehicle::STATUS_AVAILABLE,
            Vehicle::STATUS_MAINTENANCE,
        ],
        Vehicle::STATUS_MAINTENANCE => [
            Vehicle::STATUS_INSPECTION_REQUIRED,
            Vehicle::STATUS_UNAVAILABLE,
            Vehicle::STATUS_AVAILABLE,
        ],
        Vehicle::STATUS_UNAVAILABLE => [
            Vehicle::STATUS_MAINTENANCE,
            Vehicle::STATUS_AVAILABLE,
            Vehicle::STATUS_RETIRED,
        ],
        Vehicle::STATUS_TRANSFER_PENDING => [
            Vehicle::STATUS_TRANSFER_IN_TRANSIT,
            Vehicle::STATUS_AVAILABLE,
        ],
        Vehicle::STATUS_TRANSFER_IN_TRANSIT => [
            Vehicle::STATUS_INSPECTION_REQUIRED,
            Vehicle::STATUS_AVAILABLE,
        ],
        Vehicle::STATUS_TRANSFERRED => [
            Vehicle::STATUS_AVAILABLE,
            Vehicle::STATUS_INSPECTION_REQUIRED,
        ],
        Vehicle::STATUS_RETIRED => [],
    ];

    public function __construct(
        private AuditLogService $auditLog
    ) {}

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function transition(
        Vehicle $vehicle,
        string $newStatus,
        User $actor,
        ?string $notes = null,
        bool $force = false
    ): Vehicle {
        $oldStatus = $vehicle->status;

        if ($oldStatus === $newStatus) {
            return $vehicle;
        }

        if (!$force && !$this->canTransition($oldStatus, $newStatus)) {
            throw new InvalidArgumentException(
                "Cannot change vehicle status from {$oldStatus} to {$newStatus}."
            );
        }

        if (!$force && $newStatus === Vehicle::STATUS_AVAILABLE) {
            $this->assertCanBecomeAvailable($vehicle);
        }

        $vehicle->update(['status' => $newStatus]);

        $this->auditLog->log(
            $actor,
            'vehicle_status_changed',
            'vehicle',
            $vehicle->id,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $notes,
            $vehicle->branch_id
        );

        return $vehicle->fresh();
    }

    public function updateMileage(Vehicle $vehicle, int $newMileage, User $actor, bool $allowCorrection = false): Vehicle
    {
        $current = (int) ($vehicle->mileage ?? 0);

        if ($newMileage < $current && !$allowCorrection) {
            throw new InvalidArgumentException(
                'Mileage cannot decrease without an authorized correction.'
            );
        }

        if ($newMileage === $current) {
            return $vehicle;
        }

        $vehicle->update(['mileage' => $newMileage]);

        $this->auditLog->log(
            $actor,
            'vehicle_mileage_updated',
            'vehicle',
            $vehicle->id,
            ['mileage' => $current],
            ['mileage' => $newMileage],
            $allowCorrection ? 'Authorized mileage correction' : null,
            $vehicle->branch_id
        );

        return $vehicle->fresh();
    }

    public function assertCanBecomeAvailable(Vehicle $vehicle): void
    {
        if ($vehicle->hasExpiredRequiredDocuments()) {
            throw new InvalidArgumentException(
                'Vehicle has expired required documents and cannot be made available.'
            );
        }

        if ($vehicle->hasBlockingActiveBooking()) {
            throw new InvalidArgumentException(
                'Vehicle has an active confirmed booking and cannot be made available.'
            );
        }

        $pendingInspection = $vehicle->inspections()
            ->where('inspection_type', VehicleInspection::TYPE_POST_RETURN)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        if ($pendingInspection) {
            throw new InvalidArgumentException(
                'Post-return inspection must be completed before the vehicle becomes available.'
            );
        }
    }
}
