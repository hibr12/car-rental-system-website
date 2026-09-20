<?php

namespace App\Listeners;

use App\Events\MaintenanceCreated;
use App\Notifications\MaintenanceCreated as MaintenanceCreatedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendMaintenanceCreatedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(MaintenanceCreated $event): void
    {
        try {
            $maintenance = $event->maintenance->loadMissing(['vehicle', 'branch']);
            $recipients = $this->notificationRecipients->adminsAndFleetManagersAndBranchManagers((int) $maintenance->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new MaintenanceCreatedNotification($maintenance));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send maintenance created notification', [
                'maintenance_id' => $event->maintenance->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}