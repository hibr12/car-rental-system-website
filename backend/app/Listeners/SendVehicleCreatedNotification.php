<?php

namespace App\Listeners;

use App\Events\VehicleCreated;
use App\Notifications\VehicleCreated as VehicleCreatedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendVehicleCreatedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(VehicleCreated $event): void
    {
        try {
            $vehicle = $event->vehicle->loadMissing(['branch']);
            $recipients = $this->notificationRecipients->adminsAndFleetManagersAndBranchManagers((int) $vehicle->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new VehicleCreatedNotification($vehicle));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send vehicle created notification', [
                'vehicle_id' => $event->vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}