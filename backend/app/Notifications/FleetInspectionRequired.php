<?php

namespace App\Notifications;

use App\Models\Vehicle;
use App\Models\VehicleInspection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FleetInspectionRequired extends Notification
{
    use Queueable;

    public function __construct(
        public Vehicle $vehicle,
        public VehicleInspection $inspection
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'fleet_inspection_required',
            'title' => 'Vehicle Requires Maintenance',
            'message' => sprintf(
                'Inspection for %s %s requires maintenance attention.',
                $this->vehicle->brand,
                $this->vehicle->model
            ),
            'vehicle_id' => $this->vehicle->id,
            'inspection_id' => $this->inspection->id,
        ];
    }
}
