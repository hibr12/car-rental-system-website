<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewSubmitted extends Notification
{
    use Queueable;

    public Review $review;

    public function __construct(Review $review)
    {
        $this->review = $review;
    }

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
            ->line('Rating: ' . $this->review->rating . '/5')
            ->line($this->review->comment ? 'Comment: "' . $this->review->comment . '"' : '')
            ->action('View Vehicle', url('/api/vehicles/' . $this->review->vehicle_id))
            ->line('Thank you for sharing your feedback!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'vehicle_id' => $this->review->vehicle_id,
            'rating' => $this->review->rating,
            'title' => 'Review Submitted',
            'message' => 'Your review for ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model . ' has been submitted.',
            'type' => 'review_submitted',
            'created_at' => now()->toISOString(),
        ];
    }
}
