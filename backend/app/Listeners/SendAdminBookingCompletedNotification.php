<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Models\User;
use App\Notifications\AdminBookingCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminBookingCompletedNotification implements ShouldQueue
{
    public function handle(BookingCompleted $event): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminBookingCompleted($event->booking));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin booking completed notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
