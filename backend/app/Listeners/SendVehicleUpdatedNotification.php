<?php

namespace App\Listeners;

use App\Events\VehicleUpdated;
use App\Notifications\VehicleUpdated as VehicleUpdatedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendVehicleUpdatedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(VehicleUpdated $event): void
    {
        try {
            $vehicle = $event->vehicle->loadMissing(['branch']);
            $recipients = $this->notificationRecipients->adminsAndFleetManagersAndBranchManagers((int) $vehicle->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new VehicleUpdatedNotification($vehicle, $event->changes));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send vehicle updated notification', [
                'vehicle_id' => $event->vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}