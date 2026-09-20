<?php

namespace App\Listeners;

use App\Events\VehicleTransferStatusChanged;
use App\Notifications\VehicleTransferStatusChanged as VehicleTransferStatusChangedNotification;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendVehicleTransferStatusChangedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(VehicleTransferStatusChanged $event): void
    {
        try {
            $transfer = $event->transfer->loadMissing(['vehicle', 'fromBranch', 'toBranch']);
            
            // Notify both branches
            $fromBranchRecipients = $this->notificationRecipients->adminsAndBranchManagers((int) $transfer->from_branch_id);
            foreach ($fromBranchRecipients as $recipient) {
                $recipient->notify(new VehicleTransferStatusChangedNotification($transfer, $event->oldStatus, $event->newStatus));
            }

            $toBranchRecipients = $this->notificationRecipients->adminsAndBranchManagers((int) $transfer->to_branch_id);
            foreach ($toBranchRecipients as $recipient) {
                $recipient->notify(new VehicleTransferStatusChangedNotification($transfer, $event->oldStatus, $event->newStatus));
            }

            // Also notify admins and fleet managers
            $adminRecipients = $this->notificationRecipients->adminsAndFleetManagers();
            foreach ($adminRecipients as $recipient) {
                $recipient->notify(new VehicleTransferStatusChangedNotification($transfer, $event->oldStatus, $event->newStatus));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send vehicle transfer status changed notification', [
                'transfer_id' => $event->transfer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}