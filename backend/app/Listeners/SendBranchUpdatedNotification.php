<?php

namespace App\Listeners;

use App\Events\BranchUpdated;
use App\Notifications\BranchUpdated as BranchUpdatedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBranchUpdatedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(BranchUpdated $event): void
    {
        try {
            $branch = $event->branch;
            $recipients = $this->notificationRecipients->admins();

            foreach ($recipients as $recipient) {
                $recipient->notify(new BranchUpdatedNotification($branch, $event->changes));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send branch updated notification', [
                'branch_id' => $event->branch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}