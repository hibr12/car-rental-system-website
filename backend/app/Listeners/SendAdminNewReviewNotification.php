<?php

namespace App\Listeners;

use App\Events\ReviewCreated;
use App\Models\User;
use App\Notifications\AdminNewReview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminNewReviewNotification implements ShouldQueue
{
    public function handle(ReviewCreated $event): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'staff'])->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminNewReview($event->review));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin new review notification', [
                'review_id' => $event->review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
