<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaintenanceRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(public MaintenanceRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $vehicle = $this->request->vehicle;
        $branch = $this->request->branch;

        return [
            'type' => 'maintenance_request_submitted',
            'title' => 'New Maintenance Request',
            'message' => sprintf(
                '%s requested maintenance for %s %s at %s.',
                $this->request->requester?->name ?? 'Branch manager',
                $vehicle?->brand,
                $vehicle?->model,
                $branch?->name
            ),
            'maintenance_request_id' => $this->request->id,
            'vehicle_id' => $this->request->vehicle_id,
            'branch_id' => $this->request->branch_id,
            'priority' => $this->request->priority,
        ];
    }
}
