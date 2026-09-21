<?php

namespace App\Listeners;

use App\Events\MaintenanceUpdated;
use App\Notifications\MaintenanceUpdated as MaintenanceUpdatedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendMaintenanceUpdatedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(MaintenanceUpdated $event): void
    {
        try {
            $maintenance = $event->maintenance->loadMissing(['vehicle', 'branch']);
            $recipients = $this->notificationRecipients->adminsAndFleetManagersAndBranchManagers((int) $maintenance->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new MaintenanceUpdatedNotification($maintenance, $event->changes));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send maintenance updated notification', [
                'maintenance_id' => $event->maintenance->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}