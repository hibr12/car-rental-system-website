<?php

namespace App\Notifications;

use App\Models\Maintenance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public Maintenance $maintenance,
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
            'maintenance_id' => $this->maintenance->id,
            'vehicle_id' => $this->maintenance->vehicle_id,
            'vehicle_name' => $this->maintenance->vehicle?->brand . ' ' . $this->maintenance->vehicle?->model,
            'title' => $this->maintenance->title,
            'maintenance_type' => $this->maintenance->maintenance_type,
            'status' => $this->maintenance->status,
            'changes' => $this->changes,
            'branch_id' => $this->maintenance->branch_id,
            'title' => 'Maintenance Updated',
            'message' => 'Maintenance "' . $this->maintenance->title . '" has been updated. Changes: ' . $changeList,
            'type' => 'maintenance_updated',
            'created_at' => now()->toISOString(),
        ];
    }
}