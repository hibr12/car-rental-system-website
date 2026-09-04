<?php

namespace App\Listeners;

use App\Events\ReviewCreated;
use App\Services\NotificationRecipientService;
use App\Notifications\AdminNewReview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminNewReviewNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $recipients
    ) {}

    public function handle(ReviewCreated $event): void
    {
        try {
            $review = $event->review->load(['user', 'vehicle', 'branch']);
            $recipients = $this->recipients->adminsAndBranchManagers($review->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new AdminNewReview($review));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin new review notification', [
                'review_id' => $event->review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
