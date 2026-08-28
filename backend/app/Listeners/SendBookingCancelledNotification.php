<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Notifications\BookingCancelled as BookingCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBookingCancelledNotification implements ShouldQueue
{
    public function handle(BookingCancelled $event): void
    {
        try {
            $event->booking->user->notify(
                new BookingCancelledNotification($event->booking)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send booking cancelled notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
