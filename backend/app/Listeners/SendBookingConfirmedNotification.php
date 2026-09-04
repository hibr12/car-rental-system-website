<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Notifications\BookingConfirmed as BookingConfirmedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBookingConfirmedNotification implements ShouldQueue
{
    public function handle(BookingConfirmed $event): void
    {
        try {
            $event->booking->user->notify(
                new BookingConfirmedNotification($event->booking)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmed notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
