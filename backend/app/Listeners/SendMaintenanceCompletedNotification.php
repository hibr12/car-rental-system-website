<?php

namespace App\Listeners;

use App\Events\MaintenanceCompleted;
use App\Notifications\MaintenanceCompleted as MaintenanceCompletedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendMaintenanceCompletedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(MaintenanceCompleted $event): void
    {
        try {
            $maintenance = $event->maintenance->loadMissing(['vehicle', 'branch']);
            $recipients = $this->notificationRecipients->adminsAndFleetManagersAndBranchManagers((int) $maintenance->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new MaintenanceCompletedNotification($maintenance));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send maintenance completed notification', [
                'maintenance_id' => $event->maintenance->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}