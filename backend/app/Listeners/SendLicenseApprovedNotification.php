<?php

namespace App\Listeners;

use App\Events\LicenseApproved;
use App\Notifications\LicenseApproved as LicenseApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendLicenseApprovedNotification implements ShouldQueue
{
    public function handle(LicenseApproved $event): void
    {
        try {
            $license = $event->license->loadMissing(['user']);
            $license->user->notify(new LicenseApprovedNotification($license));
        } catch (\Exception $e) {
            Log::error('Failed to send license approved notification', [
                'license_id' => $event->license->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}