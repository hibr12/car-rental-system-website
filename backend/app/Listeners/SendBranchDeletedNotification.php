<?php

namespace App\Listeners;

use App\Events\BranchDeleted;
use App\Notifications\BranchDeleted as BranchDeletedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBranchDeletedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(BranchDeleted $event): void
    {
        try {
            $recipients = $this->notificationRecipients->admins();

            foreach ($recipients as $recipient) {
                $recipient->notify(new BranchDeletedNotification(
                    $event->branchId,
                    $event->branchName
                ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send branch deleted notification', [
                'branch_id' => $event->branchId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}