<?php

namespace App\Notifications;

use App\Models\Maintenance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceCompleted extends Notification
{
    use Queueable;

    public function __construct(
        public Maintenance $maintenance
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'maintenance_id' => $this->maintenance->id,
            'vehicle_id' => $this->maintenance->vehicle_id,
            'vehicle_name' => $this->maintenance->vehicle?->brand . ' ' . $this->maintenance->vehicle?->model,
            'title' => $this->maintenance->title,
            'maintenance_type' => $this->maintenance->maintenance_type,
            'branch_id' => $this->maintenance->branch_id,
            'title' => 'Maintenance Completed',
            'message' => 'Maintenance "' . $this->maintenance->title . '" for ' . ($this->maintenance->vehicle?->brand . ' ' . $this->maintenance->vehicle?->model ?? 'vehicle') . ' has been completed.',
            'type' => 'maintenance_completed',
            'created_at' => now()->toISOString(),
        ];
    }
}