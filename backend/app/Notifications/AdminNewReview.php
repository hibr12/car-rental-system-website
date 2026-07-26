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
        return (new MailMessage)
            ->subject('New Review Submitted - ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new review has been submitted by a customer.')
            ->line('Vehicle: ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model)
            ->line('Customer: ' . $this->review->user->name)
            ->line('Rating: ' . $this->review->rating . '/5')
            ->line($this->review->comment ? 'Comment: "' . $this->review->comment . '"' : 'No comment provided.')
            ->action('View Review', url('/api/vehicles/' . $this->review->vehicle_id . '/reviews'))
            ->line('You may review and moderate this submission if needed.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'vehicle_id' => $this->review->vehicle_id,
            'customer_name' => $this->review->user->name,
            'rating' => $this->review->rating,
            'vehicle' => $this->review->vehicle->brand . ' ' . $this->review->vehicle->model,
            'title' => 'New Review Submitted',
            'message' => $this->review->user->name . ' submitted a ' . $this->review->rating . '/5 review for ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model . '.',
            'type' => 'admin_new_review',
            'created_at' => now()->toISOString(),
        ];
    }
}
