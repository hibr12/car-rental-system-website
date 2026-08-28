<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Models\User;
use App\Notifications\AdminBookingCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminBookingCancelledNotification implements ShouldQueue
{
    public function handle(BookingCancelled $event): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminBookingCancelled($event->booking));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin booking cancelled notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
