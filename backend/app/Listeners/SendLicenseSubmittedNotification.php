<?php

namespace App\Listeners;

use App\Events\LicenseSubmitted;
use App\Notifications\LicenseSubmittedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendLicenseSubmittedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(LicenseSubmitted $event): void
    {
        try {
            $license = $event->license->loadMissing(['user']);
            $recipients = $this->notificationRecipients->adminsAndBranchManagers((int) $license->user->branch_id ?? 0);

            foreach ($recipients as $recipient) {
                $recipient->notify(new LicenseSubmittedNotification($license));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send license submitted notification', [
                'license_id' => $event->license->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}