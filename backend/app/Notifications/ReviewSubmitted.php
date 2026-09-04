<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewSubmitted extends Notification
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
            ->subject('Review Submitted - Thank You!')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your review has been submitted successfully.')
            ->line('Vehicle: ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model)
            ->line('Overall Rating: ' . $this->review->overall_rating . '/5')
            ->line($this->review->comment ? 'Comment: "' . $this->review->comment . '"' : '')
            ->line('Thank you for sharing your feedback with Apex Rentals!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'vehicle_id' => $this->review->vehicle_id,
            'overall_rating' => $this->review->overall_rating,
            'title' => 'Review Submitted',
            'message' => 'Your review for ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model . ' has been submitted.',
            'type' => 'review_submitted',
            'created_at' => now()->toISOString(),
        ];
    }
}
