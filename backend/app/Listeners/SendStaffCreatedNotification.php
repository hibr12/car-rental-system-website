<?php

namespace App\Listeners;

use App\Events\StaffCreated;
use App\Notifications\StaffCreated as StaffCreatedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendStaffCreatedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(StaffCreated $event): void
    {
        try {
            $staff = $event->staff->loadMissing(['branch']);
            $recipients = $this->notificationRecipients->adminsAndBranchManagers((int) $staff->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new StaffCreatedNotification($staff));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send staff created notification', [
                'staff_id' => $event->staff->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}