<?php

namespace App\Listeners;

use App\Events\BookingRejected;
use App\Notifications\BookingRejected as BookingRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBookingRejectedNotification implements ShouldQueue
{
    public function handle(BookingRejected $event): void
    {
        try {
            $event->booking->user->notify(
                new BookingRejectedNotification($event->booking, $event->reason)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send booking rejected notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
