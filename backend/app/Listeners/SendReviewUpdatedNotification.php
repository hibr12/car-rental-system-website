<?php

namespace App\Listeners;

use App\Events\ReviewUpdated;
use App\Notifications\ReviewUpdated as ReviewUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendReviewUpdatedNotification implements ShouldQueue
{
    public function handle(ReviewUpdated $event): void
    {
        try {
            $event->review->user->notify(
                new ReviewUpdatedNotification($event->review)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send review updated notification', [
                'review_id' => $event->review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
