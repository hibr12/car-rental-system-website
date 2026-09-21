<?php

namespace App\Listeners;

use App\Events\VehicleTransferCreated;
use App\Notifications\VehicleTransferCreated as VehicleTransferCreatedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendVehicleTransferCreatedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(VehicleTransferCreated $event): void
    {
        try {
            $transfer = $event->transfer->loadMissing(['vehicle', 'fromBranch', 'toBranch']);
            
            // Notify from branch
            $fromBranchRecipients = $this->notificationRecipients->adminsAndBranchManagers((int) $transfer->from_branch_id);
            foreach ($fromBranchRecipients as $recipient) {
                $recipient->notify(new VehicleTransferCreatedNotification($transfer));
            }

            // Notify to branch
            $toBranchRecipients = $this->notificationRecipients->adminsAndBranchManagers((int) $transfer->to_branch_id);
            foreach ($toBranchRecipients as $recipient) {
                $recipient->notify(new VehicleTransferCreatedNotification($transfer));
            }

            // Also notify admins and fleet managers
            $adminRecipients = $this->notificationRecipients->adminsAndFleetManagers();
            foreach ($adminRecipients as $recipient) {
                $recipient->notify(new VehicleTransferCreatedNotification($transfer));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send vehicle transfer created notification', [
                'transfer_id' => $event->transfer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}