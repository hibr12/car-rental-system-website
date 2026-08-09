<?php

namespace App\Listeners;

use App\Events\BookingRejected;
use App\Models\User;
use App\Notifications\AdminBookingRejected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminBookingRejectedNotification implements ShouldQueue
{
    public function handle(BookingRejected $event): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminBookingRejected($event->booking, $event->reason));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin booking rejected notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
