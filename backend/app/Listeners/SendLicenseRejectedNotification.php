<?php

namespace App\Listeners;

use App\Events\LicenseRejected;
use App\Notifications\LicenseRejected as LicenseRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendLicenseRejectedNotification implements ShouldQueue
{
    public function handle(LicenseRejected $event): void
    {
        try {
            $license = $event->license->loadMissing(['user']);
            $license->user->notify(new LicenseRejectedNotification($license, $event->reason));
        } catch (\Exception $e) {
            Log::error('Failed to send license rejected notification', [
                'license_id' => $event->license->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}