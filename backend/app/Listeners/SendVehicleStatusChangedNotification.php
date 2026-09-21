<?php

namespace App\Listeners;

use App\Events\VehicleStatusChanged;
use App\Notifications\VehicleStatusChanged as VehicleStatusChangedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendVehicleStatusChangedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(VehicleStatusChanged $event): void
    {
        try {
            $vehicle = $event->vehicle->loadMissing(['branch']);
            $recipients = $this->notificationRecipients->adminsAndFleetManagersAndBranchManagers((int) $vehicle->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new VehicleStatusChangedNotification($vehicle, $event->oldStatus, $event->newStatus));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send vehicle status changed notification', [
                'vehicle_id' => $event->vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}