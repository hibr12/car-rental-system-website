<?php

namespace App\Listeners;

use App\Events\StaffDeleted;
use App\Notifications\StaffDeleted as StaffDeletedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendStaffDeletedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(StaffDeleted $event): void
    {
        try {
            $recipients = $this->notificationRecipients->adminsAndBranchManagers($event->branchId);

            foreach ($recipients as $recipient) {
                $recipient->notify(new StaffDeletedNotification(
                    $event->staffId,
                    $event->staffName,
                    $event->branchId
                ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send staff deleted notification', [
                'staff_id' => $event->staffId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}