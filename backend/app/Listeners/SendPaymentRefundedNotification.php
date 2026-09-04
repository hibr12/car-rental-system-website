<?php

namespace App\Listeners;

use App\Events\PaymentRefunded;
use App\Notifications\PaymentRefunded as PaymentRefundedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentRefundedNotification implements ShouldQueue
{
    public function handle(PaymentRefunded $event): void
    {
        try {
            $event->booking->user->notify(
                new PaymentRefundedNotification($event->booking, $event->payment)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send payment refunded notification', [
                'payment_id' => $event->payment->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
