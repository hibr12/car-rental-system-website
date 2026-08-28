<?php

namespace App\Listeners;

use App\Events\ReviewCreated;
use App\Notifications\ReviewSubmitted as ReviewSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendReviewCreatedNotification implements ShouldQueue
{
    public function handle(ReviewCreated $event): void
    {
        try {
            $event->review->user->notify(
                new ReviewSubmittedNotification($event->review)
            );
        } catch (\Exception $e) {
            Log::error('Failed to send review created notification', [
                'review_id' => $event->review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
