<?php

namespace App\Listeners;

use App\Events\VehicleDeleted;
use App\Notifications\VehicleDeleted as VehicleDeletedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendVehicleDeletedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(VehicleDeleted $event): void
    {
        try {
            $recipients = $this->notificationRecipients->adminsAndFleetManagersAndBranchManagers($event->branchId);

            foreach ($recipients as $recipient) {
                $recipient->notify(new VehicleDeletedNotification(
                    $event->vehicleId,
                    $event->vehicleName,
                    $event->branchId
                ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send vehicle deleted notification', [
                'vehicle_id' => $event->vehicleId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}