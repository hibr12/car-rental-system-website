<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Models\User;
use App\Notifications\AdminNewBooking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminNewBookingNotification implements ShouldQueue
{
    public function handle(BookingCreated $event): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminNewBooking($event->booking));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin new booking notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
