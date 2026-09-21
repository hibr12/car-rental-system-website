<?php

namespace App\Notifications;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public Vehicle $vehicle,
        public array $changes
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $changeList = implode(', ', array_keys($this->changes));
        return [
            'vehicle_id' => $this->vehicle->id,
            'vehicle_name' => $this->vehicle->brand . ' ' . $this->vehicle->model,
            'registration_number' => $this->vehicle->registration_number,
            'branch_id' => $this->vehicle->branch_id,
            'changes' => $this->changes,
            'title' => 'Vehicle Updated',
            'message' => 'Vehicle ' . $this->vehicle->brand . ' ' . $this->vehicle->model . ' has been updated. Changes: ' . $changeList,
            'type' => 'vehicle_updated',
            'created_at' => now()->toISOString(),
        ];
    }
}