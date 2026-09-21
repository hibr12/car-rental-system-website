<?php

namespace App\Notifications;

use App\Models\VehicleTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleTransferCompleted extends Notification
{
    use Queueable;

    public function __construct(
        public VehicleTransfer $transfer
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'transfer_id' => $this->transfer->id,
            'vehicle_id' => $this->transfer->vehicle_id,
            'vehicle_name' => $this->transfer->vehicle?->brand . ' ' . $this->transfer->vehicle?->model,
            'from_branch_id' => $this->transfer->from_branch_id,
            'from_branch_name' => $this->transfer->fromBranch?->name,
            'to_branch_id' => $this->transfer->to_branch_id,
            'to_branch_name' => $this->transfer->toBranch?->name,
            'title' => 'Vehicle Transfer Completed',
            'message' => 'Vehicle transfer from ' . ($this->transfer->fromBranch?->name ?? 'unknown') . ' to ' . ($this->transfer->toBranch?->name ?? 'unknown') . ' has been completed.',
            'type' => 'vehicle_transfer_completed',
            'created_at' => now()->toISOString(),
        ];
    }
}