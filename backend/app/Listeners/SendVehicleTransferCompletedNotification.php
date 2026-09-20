<?php

namespace App\Listeners;

use App\Events\VehicleTransferCompleted;
use App\Notifications\VehicleTransferCompleted as VehicleTransferCompletedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendVehicleTransferCompletedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(VehicleTransferCompleted $event): void
    {
        try {
            $transfer = $event->transfer->loadMissing(['vehicle', 'fromBranch', 'toBranch']);
            
            // Notify both branches
            $fromBranchRecipients = $this->notificationRecipients->adminsAndBranchManagers((int) $transfer->from_branch_id);
            foreach ($fromBranchRecipients as $recipient) {
                $recipient->notify(new VehicleTransferCompletedNotification($transfer));
            }

            $toBranchRecipients = $this->notificationRecipients->adminsAndBranchManagers((int) $transfer->to_branch_id);
            foreach ($toBranchRecipients as $recipient) {
                $recipient->notify(new VehicleTransferCompletedNotification($transfer));
            }

            // Also notify admins and fleet managers
            $adminRecipients = $this->notificationRecipients->adminsAndFleetManagers();
            foreach ($adminRecipients as $recipient) {
                $recipient->notify(new VehicleTransferCompletedNotification($transfer));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send vehicle transfer completed notification', [
                'transfer_id' => $event->transfer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}