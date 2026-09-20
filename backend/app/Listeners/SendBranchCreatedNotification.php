<?php

namespace App\Listeners;

use App\Events\BranchCreated;
use App\Notifications\BranchCreated as BranchCreatedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBranchCreatedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(BranchCreated $event): void
    {
        try {
            $branch = $event->branch;
            $recipients = $this->notificationRecipients->admins();

            foreach ($recipients as $recipient) {
                $recipient->notify(new BranchCreatedNotification($branch));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send branch created notification', [
                'branch_id' => $event->branch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}