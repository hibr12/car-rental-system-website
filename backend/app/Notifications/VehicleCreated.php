<?php

namespace App\Notifications;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleCreated extends Notification
{
    use Queueable;

    public function __construct(
        public Vehicle $vehicle
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'vehicle_id' => $this->vehicle->id,
            'vehicle_name' => $this->vehicle->brand . ' ' . $this->vehicle->model,
            'registration_number' => $this->vehicle->registration_number,
            'branch_id' => $this->vehicle->branch_id,
            'title' => 'Vehicle Added',
            'message' => 'New vehicle ' . $this->vehicle->brand . ' ' . $this->vehicle->model . ' (' . $this->vehicle->registration_number . ') has been added.',
            'type' => 'vehicle_created',
            'created_at' => now()->toISOString(),
        ];
    }
}