<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Notifications\PaymentSuccess as PaymentSuccessNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentSuccessNotification implements ShouldQueue
{
    public function handle(PaymentSucceeded $event): void
    {
        try {
            $event->booking->user->notify(
                new PaymentSuccessNotification($event->booking, $event->payment)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send payment success notification', [
                'payment_id' => $event->payment->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
