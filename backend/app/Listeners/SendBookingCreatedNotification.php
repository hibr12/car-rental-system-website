<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Notifications\BookingCreated as BookingCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBookingCreatedNotification implements ShouldQueue
{
    public function handle(BookingCreated $event): void
    {
        try {
            $event->booking->user->notify(
                new BookingCreatedNotification($event->booking)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send booking created notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
