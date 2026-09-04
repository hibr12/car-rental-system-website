<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDamage;
use App\Models\VehicleDocument;
use App\Models\VehicleInspection;
use App\Notifications\FleetDocumentExpiringSoon;
use Illuminate\Support\Facades\Notification;

class VehicleDocumentService
{
    public function __construct(
        private AuditLogService $auditLog
    ) {}

    public function create(array $data, User $actor): VehicleDocument
    {
        $document = VehicleDocument::create(array_merge($data, [
            'created_by' => $actor->id,
        ]));

        $document->refreshStatus();
        $this->enforceVehicleCompliance($document->vehicle);

        $this->auditLog->log(
            $actor,
            'document_created',
            'vehicle_document',
            $document->id,
            null,
            ['vehicle_id' => $document->vehicle_id, 'type' => $document->document_type],
            null,
            $document->vehicle?->branch_id
        );

        return $document->fresh()->load('vehicle');
    }

    public function update(VehicleDocument $document, array $data, User $actor): VehicleDocument
    {
        $old = $document->only(['document_type', 'expiry_date', 'status']);
        $document->update($data);
        $document->refreshStatus();
        $this->enforceVehicleCompliance($document->vehicle);

        $this->auditLog->log(
            $actor,
            'document_updated',
            'vehicle_document',
            $document->id,
            $old,
            $document->only(['document_type', 'expiry_date', 'status']),
            null,
            $document->vehicle?->branch_id
        );

        if ($document->status === VehicleDocument::STATUS_EXPIRING_SOON) {
            $this->notifyExpiring($document);
        }

        return $document->fresh()->load('vehicle');
    }

    public function refreshAllForVehicle(Vehicle $vehicle): void
    {
        foreach ($vehicle->documents as $document) {
            $document->refreshStatus();
        }

        $this->enforceVehicleCompliance($vehicle);
    }

    public function enforceVehicleCompliance(?Vehicle $vehicle): void
    {
        if (!$vehicle) {
            return;
        }

        if ($vehicle->hasExpiredRequiredDocuments()
            && !in_array($vehicle->status, [Vehicle::STATUS_MAINTENANCE, Vehicle::STATUS_RETIRED], true)) {
            $vehicle->update(['status' => Vehicle::STATUS_UNAVAILABLE]);
        }
    }

    private function notifyExpiring(VehicleDocument $document): void
    {
        $days = now()->diffInDays($document->expiry_date, false);
        $fleetManagers = User::where('role', User::ROLE_FLEET_MANAGER)->get();

        if ($fleetManagers->isNotEmpty()) {
            Notification::send($fleetManagers, new FleetDocumentExpiringSoon($document, max(0, (int) $days)));
        }
    }
}
