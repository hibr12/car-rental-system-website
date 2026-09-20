<?php

namespace App\Notifications;

use App\Models\Maintenance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceCreated extends Notification
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
            'status' => $this->maintenance->status,
            'start_date' => $this->maintenance->start_date?->toISOString(),
            'branch_id' => $this->maintenance->branch_id,
            'title' => 'Maintenance Scheduled',
            'message' => 'Maintenance "' . $this->maintenance->title . '" has been scheduled for ' . ($this->maintenance->vehicle?->brand . ' ' . $this->maintenance->vehicle?->model ?? 'vehicle') . '.',
            'type' => 'maintenance_created',
            'created_at' => now()->toISOString(),
        ];
    }
}