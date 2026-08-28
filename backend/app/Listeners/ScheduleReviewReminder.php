<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Jobs\SendReviewReminderJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class ScheduleReviewReminder implements ShouldQueue
{
    public function handle(BookingCompleted $event): void
    {
        SendReviewReminderJob::dispatch($event->booking->id)
            ->delay(now()->addDays(3));
    }
}
