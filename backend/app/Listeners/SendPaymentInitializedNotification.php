<?php

namespace App\Listeners;

use App\Events\PaymentCreated;
use App\Models\User;
use App\Models\Payment;
use App\Notifications\AdminPaymentInitialized;
use App\Notifications\AdminCashPaymentInitialized;
use App\Notifications\PaymentInitialized;
use App\Notifications\CashPaymentInitialized;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentInitializedNotification implements ShouldQueue
{
    public function handle(PaymentCreated $event): void
    {
        try {
            // Avoid N+1 access in notification serialization.
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
            if (($event->payment->payment_method ?? '') === Payment::METHOD_CASH) {
                $branchStaff = User::query()
                    ->where('branch_id', (int) $event->payment->branch_id)
                    ->whereIn('role', ['branch_manager', 'staff'])
                    ->get();

                foreach ($branchStaff as $user) {
                    $user->notify(new AdminCashPaymentInitialized($event->booking, $event->payment));
                }
            } else {
                $admins = User::whereIn('role', ['admin', 'staff'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new AdminPaymentInitialized($event->booking, $event->payment));
                }
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
