<?php

namespace App\Notifications;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Vehicle $vehicle,
        public string $oldStatus,
        public string $newStatus
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'title' => 'Vehicle Status Changed',
            'message' => 'Vehicle ' . $this->vehicle->brand . ' ' . $this->vehicle->model . ' status changed from ' . $this->oldStatus . ' to ' . $this->newStatus . '.',
            'type' => 'vehicle_status_changed',
            'created_at' => now()->toISOString(),
        ];
    }
}