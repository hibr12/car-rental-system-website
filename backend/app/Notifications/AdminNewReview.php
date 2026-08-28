<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminNewReview extends Notification
{
    use Queueable;

    public function __construct(
        public Review $review
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');

        return (new MailMessage)
            ->subject('New Customer Review Received')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new customer review has been received.')
            ->line('Customer: ' . $this->review->user->name)
            ->line('Vehicle: ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model)
            ->line('Branch: ' . ($this->review->branch->name ?? 'N/A'))
            ->line('Overall Rating: ' . $this->review->overall_rating . '/5')
            ->line($this->review->comment ? 'Comment: "' . $this->review->comment . '"' : 'No comment provided.')
            ->action('Manage Reviews', $frontendUrl . '/admin/reviews')
            ->line('You may review and moderate this submission if needed.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'vehicle_id' => $this->review->vehicle_id,
            'branch_id' => $this->review->branch_id,
            'customer_name' => $this->review->user->name,
            'overall_rating' => $this->review->overall_rating,
            'vehicle' => $this->review->vehicle->brand . ' ' . $this->review->vehicle->model,
            'title' => 'New Customer Review',
            'message' => $this->review->user->name . ' submitted a ' . $this->review->overall_rating . '/5 review.',
            'type' => 'admin_new_review',
            'created_at' => now()->toISOString(),
        ];
    }
}
