<?php

namespace App\Listeners;

use App\Events\StaffUpdated;
use App\Notifications\StaffUpdated as StaffUpdatedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendStaffUpdatedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(StaffUpdated $event): void
    {
        try {
            $staff = $event->staff->loadMissing(['branch']);
            $recipients = $this->notificationRecipients->adminsAndBranchManagers((int) $staff->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new StaffUpdatedNotification($staff, $event->changes));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send staff updated notification', [
                'staff_id' => $event->staff->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}