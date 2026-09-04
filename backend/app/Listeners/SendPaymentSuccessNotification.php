<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Notifications\PaymentApproved;
use App\Notifications\PaymentSuccess as PaymentSuccessNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentSuccessNotification implements ShouldQueue
{
    public function handle(PaymentSucceeded $event): void
    {
        try {
            $payment = $event->payment->loadMissing('verifier');
            $booking = $event->booking->loadMissing('user');
            $verifier = $payment->verifier;

            if ($verifier && ($verifier->isAdmin() || $verifier->isBranchManager() || $verifier->isStaff())) {
                $booking->user->notify(new PaymentApproved($booking, $payment, $verifier));
            } else {
                $booking->user->notify(new PaymentSuccessNotification($booking, $payment));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payment success notification', [
                'payment_id' => $event->payment->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
