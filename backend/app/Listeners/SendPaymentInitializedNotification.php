<?php

namespace App\Listeners;

use App\Events\PaymentCreated;
use App\Models\User;
use App\Notifications\AdminPaymentInitialized;
use App\Notifications\PaymentInitialized;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentInitializedNotification implements ShouldQueue
{
    public function handle(PaymentCreated $event): void
    {
        try {
            $event->booking->user->notify(
                new PaymentInitialized($event->booking, $event->payment)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send payment initialized notification to customer', [
                'payment_id' => $event->payment->id,
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminPaymentInitialized($event->booking, $event->payment));
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
