<?php

namespace App\Listeners;

use App\Events\BookingPickedUp;
use App\Notifications\BookingPickupReminder as BookingPickupNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBookingPickedUpNotification implements ShouldQueue
{
    public function handle(BookingPickedUp $event): void
    {
        try {
            $event->booking->user->notify(
                new BookingPickupNotification($event->booking)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send booking pickup notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
