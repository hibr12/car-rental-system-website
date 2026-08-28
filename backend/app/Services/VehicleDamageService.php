<?php

namespace App\Services;

use App\Models\User;
use App\Models\VehicleDamage;
use App\Models\VehicleInspection;

class VehicleDamageService
{
    public function __construct(
        private AuditLogService $auditLog
    ) {}

    public function create(array $data, User $actor): VehicleDamage
    {
        $damage = VehicleDamage::create(array_merge($data, [
            'reported_by' => $actor->id,
            'reported_at' => $data['reported_at'] ?? now(),
        ]));

        $this->auditLog->log(
            $actor,
            'damage_reported',
            'vehicle_damage',
            $damage->id,
            null,
            ['vehicle_id' => $damage->vehicle_id, 'severity' => $damage->severity],
            null,
            $damage->vehicle?->branch_id
        );

        return $damage->load(['vehicle', 'booking', 'reporter']);
    }

    public function createFromInspection(VehicleInspection $inspection, array $data, User $actor): VehicleDamage
    {
        return $this->create([
            'vehicle_id' => $inspection->vehicle_id,
            'booking_id' => $inspection->booking_id,
            'inspection_id' => $inspection->id,
            'damage_type' => $data['damage_type'] ?? 'general',
            'description' => $data['description'] ?? $inspection->damage_notes ?? 'Damage found during inspection.',
            'severity' => $data['severity'] ?? VehicleDamage::SEVERITY_MEDIUM,
            'location' => $data['location'] ?? null,
            'photos' => $data['photos'] ?? $inspection->photos,
            'estimated_repair_cost' => $data['estimated_repair_cost'] ?? null,
            'repair_status' => VehicleDamage::REPAIR_PENDING,
            'notes' => $data['notes'] ?? null,
        ], $actor);
    }

    public function update(VehicleDamage $damage, array $data, User $actor): VehicleDamage
    {
        $old = $damage->only(['repair_status', 'severity']);
        $damage->update($data);

        if (($data['repair_status'] ?? null) === VehicleDamage::REPAIR_COMPLETED && !$damage->repaired_at) {
            $damage->update(['repaired_at' => now()]);
        }

        $this->auditLog->log(
            $actor,
            'damage_updated',
            'vehicle_damage',
            $damage->id,
            $old,
            $damage->only(['repair_status', 'severity']),
            null,
            $damage->vehicle?->branch_id
        );

        return $damage->fresh()->load(['vehicle', 'booking', 'reporter']);
    }
}
