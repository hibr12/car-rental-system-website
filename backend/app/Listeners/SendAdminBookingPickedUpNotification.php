<?php

namespace App\Listeners;

use App\Events\BookingPickedUp;
use App\Models\User;
use App\Notifications\AdminBookingPickedUp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminBookingPickedUpNotification implements ShouldQueue
{
    public function handle(BookingPickedUp $event): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminBookingPickedUp($event->booking));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin booking picked up notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
