<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\ReviewReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReviewReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId
    ) {}

    public function handle(): void
    {
        $booking = Booking::with(['user', 'vehicle', 'review'])->find($this->bookingId);

        if (!$booking) {
            return;
        }

        if ($booking->status !== Booking::STATUS_COMPLETED) {
            return;
        }

        if ($booking->review) {
            return;
        }

        if ($booking->review_reminder_sent_at) {
            return;
        }

        $booking->user->notify(new ReviewReminder($booking));

        $booking->update(['review_reminder_sent_at' => now()]);
    }
}
