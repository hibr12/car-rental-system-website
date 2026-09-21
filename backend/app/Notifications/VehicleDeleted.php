<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleDeleted extends Notification
{
    use Queueable;

    public function __construct(
        public int $vehicleId,
        public string $vehicleName,
        public ?int $branchId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'vehicle_id' => $this->vehicleId,
            'vehicle_name' => $this->vehicleName,
            'branch_id' => $this->branchId,
            'title' => 'Vehicle Removed',
            'message' => 'Vehicle ' . $this->vehicleName . ' has been removed from the fleet.',
            'type' => 'vehicle_deleted',
            'created_at' => now()->toISOString(),
        ];
    }
}