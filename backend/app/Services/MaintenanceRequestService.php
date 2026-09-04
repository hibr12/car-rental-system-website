<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\MaintenanceRequestSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MaintenanceRequestService
{
    public function __construct(
        private AuditLogService $auditLog
    ) {}

    public function create(array $data, User $actor): MaintenanceRequest
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        if ($actor->isBranchManager() && (int) $actor->branch_id !== (int) $vehicle->branch_id) {
            throw new \InvalidArgumentException('Vehicle does not belong to your branch.');
        }

        $request = MaintenanceRequest::create([
            'vehicle_id' => $vehicle->id,
            'branch_id' => $vehicle->branch_id,
            'requested_by' => $actor->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? MaintenanceRequest::PRIORITY_MEDIUM,
            'status' => MaintenanceRequest::STATUS_REQUESTED,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->auditLog->log(
            $actor,
            'maintenance_requested',
            'maintenance_request',
            $request->id,
            null,
            ['vehicle_id' => $vehicle->id, 'priority' => $request->priority],
            null,
            $vehicle->branch_id
        );

        $fleetManagers = User::where('role', User::ROLE_FLEET_MANAGER)->get();
        if ($fleetManagers->isNotEmpty()) {
            Notification::send($fleetManagers, new MaintenanceRequestSubmitted($request));
        }

        return $request->load(['vehicle', 'branch', 'requester']);
    }

    public function approve(MaintenanceRequest $request, User $actor, array $data = []): MaintenanceRequest
    {
        if (!in_array($request->status, [MaintenanceRequest::STATUS_REQUESTED], true)) {
            throw new \InvalidArgumentException('Only requested maintenance can be approved.');
        }

        return DB::transaction(function () use ($request, $actor, $data) {
            $maintenance = Maintenance::create([
                'vehicle_id' => $request->vehicle_id,
                'branch_id' => $request->branch_id,
                'title' => $request->title,
                'description' => $request->description,
                'maintenance_type' => $data['maintenance_type'] ?? Maintenance::TYPE_GENERAL_SERVICE,
                'start_date' => $data['start_date'] ?? now(),
                'status' => Maintenance::STATUS_SCHEDULED,
                'created_by' => $actor->id,
                'notes' => $request->notes,
            ]);

            $request->update([
                'status' => MaintenanceRequest::STATUS_APPROVED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'maintenance_id' => $maintenance->id,
            ]);

            $this->auditLog->log(
                $actor,
                'maintenance_request_approved',
                'maintenance_request',
                $request->id,
                ['status' => MaintenanceRequest::STATUS_REQUESTED],
                ['status' => MaintenanceRequest::STATUS_APPROVED, 'maintenance_id' => $maintenance->id],
                null,
                $request->branch_id
            );

            return $request->fresh()->load(['vehicle', 'branch', 'requester', 'maintenance']);
        });
    }

    public function reject(MaintenanceRequest $request, User $actor, string $reason): MaintenanceRequest
    {
        if ($request->status !== MaintenanceRequest::STATUS_REQUESTED) {
            throw new \InvalidArgumentException('Only requested maintenance can be rejected.');
        }

        $request->update([
            'status' => MaintenanceRequest::STATUS_REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->auditLog->log(
            $actor,
            'maintenance_request_rejected',
            'maintenance_request',
            $request->id,
            ['status' => MaintenanceRequest::STATUS_REQUESTED],
            ['status' => MaintenanceRequest::STATUS_REJECTED],
            $reason,
            $request->branch_id
        );

        return $request->fresh()->load(['vehicle', 'branch', 'requester']);
    }
}
