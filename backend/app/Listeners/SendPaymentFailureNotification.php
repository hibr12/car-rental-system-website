<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Notifications\PaymentFailed as PaymentFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentFailureNotification implements ShouldQueue
{
    public function handle(PaymentFailed $event): void
    {
        try {
            $event->booking->user->notify(
                new PaymentFailedNotification($event->booking, $event->payment)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send payment failure notification', [
                'payment_id' => $event->payment->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
