<?php

namespace App\Listeners;

use App\Events\PaymentCreated;
use App\Models\Payment;
use App\Notifications\AdminPaymentInitialized;
use App\Notifications\AdminCashPaymentInitialized;
use App\Notifications\CashPaymentInitialized;
use App\Notifications\PaymentInitialized;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentInitializedNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(PaymentCreated $event): void
    {
        try {
            $event->booking->loadMissing(['branch', 'user']);
            $event->payment->loadMissing(['branch']);
        } catch (\Throwable) {
            // Ignore loading issues; notifications can fall back to IDs.
        }

        try {
            if (($event->payment->payment_method ?? '') === Payment::METHOD_CASH) {
                $event->booking->user->notify(new CashPaymentInitialized($event->booking, $event->payment));
            } else {
                $event->booking->user->notify(new PaymentInitialized($event->booking, $event->payment));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payment initialized notification to customer', [
                'payment_id' => $event->payment->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $branchId = (int) ($event->payment->branch_id ?? $event->booking->branch_id);
            $recipients = $this->notificationRecipients->adminsAndBranchManagers($branchId);
            $isCash = ($event->payment->payment_method ?? '') === Payment::METHOD_CASH;

            foreach ($recipients as $recipient) {
                $recipient->notify(
                    $isCash
                        ? new AdminCashPaymentInitialized($event->booking, $event->payment)
                        : new AdminPaymentInitialized($event->booking, $event->payment)
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payment initialized notification to admins', [
                'payment_id' => $event->payment->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
