<?php

namespace App\Notifications;

use App\Models\VehicleTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VehicleTransferNotification extends Notification
{
    use Queueable;

    public function __construct(
        public VehicleTransfer $transfer,
        public string $eventType,
        public string $title,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $vehicle = $this->transfer->vehicle;

        return [
            'type' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'vehicle_transfer_id' => $this->transfer->id,
            'transfer_reference' => 'TR-' . str_pad((string) $this->transfer->id, 4, '0', STR_PAD_LEFT),
            'vehicle_id' => $this->transfer->vehicle_id,
            'from_branch_id' => $this->transfer->from_branch_id,
            'to_branch_id' => $this->transfer->to_branch_id,
            'status' => $this->transfer->status,
            'vehicle_label' => $vehicle ? trim("{$vehicle->brand} {$vehicle->model}") : null,
        ];
    }
}
