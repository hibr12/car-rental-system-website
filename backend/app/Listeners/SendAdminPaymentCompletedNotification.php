<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Notifications\AdminPaymentCompleted;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminPaymentCompletedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(PaymentSucceeded $event): void
    {
        try {
            $booking = $event->booking->loadMissing(['user', 'branch']);
            $payment = $event->payment->loadMissing(['branch']);
            $branchId = (int) ($payment->branch_id ?? $booking->branch_id);
            $recipients = $this->notificationRecipients->adminsAndBranchManagers($branchId);

            foreach ($recipients as $recipient) {
                $recipient->notify(new AdminPaymentCompleted($booking, $payment));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin payment completed notification', [
                'payment_id' => $event->payment->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
