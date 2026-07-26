<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Models\User;
use App\Notifications\AdminPaymentCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminPaymentCompletedNotification implements ShouldQueue
{
    public function handle(PaymentSucceeded $event): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminPaymentCompleted($event->booking, $event->payment));
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
