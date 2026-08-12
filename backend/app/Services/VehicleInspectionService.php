<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use App\Notifications\FleetInspectionRequired;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class VehicleInspectionService
{
    public function __construct(
        private VehicleStatusService $vehicleStatusService,
        private VehicleDamageService $damageService,
        private AuditLogService $auditLog
    ) {}

    public function create(array $data, User $actor): VehicleInspection
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        $inspection = VehicleInspection::create(array_merge($data, [
            'branch_id' => $data['branch_id'] ?? $vehicle->branch_id,
            'inspector_id' => $data['inspector_id'] ?? $actor->id,
            'status' => VehicleInspection::STATUS_PENDING,
            'result' => VehicleInspection::RESULT_PENDING,
        ]));

        $this->auditLog->log(
            $actor,
            'inspection_created',
            'vehicle_inspection',
            $inspection->id,
            null,
            ['vehicle_id' => $vehicle->id, 'type' => $inspection->inspection_type],
            null,
            $vehicle->branch_id
        );

        return $inspection->load(['vehicle', 'booking', 'inspector']);
    }

    public function createPostReturnInspection(Booking $booking, User $actor): VehicleInspection
    {
        return $this->create([
            'vehicle_id' => $booking->vehicle_id,
            'booking_id' => $booking->id,
            'branch_id' => $booking->branch_id,
            'inspection_type' => VehicleInspection::TYPE_POST_RETURN,
            'mileage' => $booking->return_mileage,
            'fuel_level' => $booking->return_fuel_level,
            'notes' => 'Auto-created after vehicle return.',
        ], $actor);
    }

    public function complete(VehicleInspection $inspection, array $data, User $actor): VehicleInspection
    {
        return DB::transaction(function () use ($inspection, $data, $actor) {
            $vehicle = $inspection->vehicle;

            if (isset($data['mileage'])) {
                $this->vehicleStatusService->updateMileage(
                    $vehicle,
                    (int) $data['mileage'],
                    $actor,
                    (bool) ($data['mileage_correction'] ?? false)
                );
            }

            $result = $data['result'] ?? VehicleInspection::RESULT_PASSED;

            $inspection->update(array_merge($data, [
                'result' => $result,
                'status' => VehicleInspection::STATUS_COMPLETED,
                'inspected_at' => $data['inspected_at'] ?? now(),
                'inspector_id' => $actor->id,
            ]));

            if (!empty($data['damage']) && is_array($data['damage'])) {
                $this->damageService->createFromInspection($inspection, $data['damage'], $actor);
                $result = VehicleInspection::RESULT_REQUIRES_MAINTENANCE;
                $inspection->update([
                    'result' => $result,
                    'has_damage' => true,
                ]);
            }

            $newVehicleStatus = match ($result) {
                VehicleInspection::RESULT_PASSED => Vehicle::STATUS_AVAILABLE,
                VehicleInspection::RESULT_REQUIRES_MAINTENANCE,
                VehicleInspection::RESULT_FAILED => Vehicle::STATUS_MAINTENANCE,
                default => $vehicle->status,
            };

            if ($newVehicleStatus !== $vehicle->status) {
                $this->vehicleStatusService->transition(
                    $vehicle->fresh(),
                    $newVehicleStatus,
                    $actor,
                    "Inspection #{$inspection->id} completed: {$result}",
                    true
                );
            }

            $this->auditLog->log(
                $actor,
                'inspection_completed',
                'vehicle_inspection',
                $inspection->id,
                null,
                ['result' => $result, 'vehicle_status' => $newVehicleStatus],
                null,
                $vehicle->branch_id
            );

            $this->notifyFleetManagers($vehicle, $inspection);

            return $inspection->fresh()->load(['vehicle', 'booking', 'inspector']);
        });
    }

    private function notifyFleetManagers(Vehicle $vehicle, VehicleInspection $inspection): void
    {
        if ($inspection->result !== VehicleInspection::RESULT_REQUIRES_MAINTENANCE) {
            return;
        }

        $fleetManagers = User::where('role', User::ROLE_FLEET_MANAGER)->get();
        if ($fleetManagers->isNotEmpty()) {
            Notification::send($fleetManagers, new FleetInspectionRequired($vehicle, $inspection));
        }
    }
}
