<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Notifications\BookingCompleted as BookingCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBookingCompletedNotification implements ShouldQueue
{
    public function handle(BookingCompleted $event): void
    {
        try {
            $event->booking->user->notify(
                new BookingCompletedNotification($event->booking)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send booking completed notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
