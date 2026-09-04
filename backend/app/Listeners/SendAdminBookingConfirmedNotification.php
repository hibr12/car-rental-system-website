<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Models\User;
use App\Notifications\AdminBookingConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminBookingConfirmedNotification implements ShouldQueue
{
    public function handle(BookingConfirmed $event): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminBookingConfirmed($event->booking));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin booking confirmed notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
