<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewUpdated extends Notification
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
            ->subject('Review Updated - ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your review has been updated successfully.')
            ->line('Vehicle: ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model)
            ->line('Updated Rating: ' . $this->review->rating . '/5')
            ->line($this->review->comment ? 'Comment: "' . $this->review->comment . '"' : '')
            ->action('View Vehicle', url('/api/vehicles/' . $this->review->vehicle_id))
            ->line('Thank you for keeping your feedback up to date!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'vehicle_id' => $this->review->vehicle_id,
            'rating' => $this->review->rating,
            'title' => 'Review Updated',
            'message' => 'Your review for ' . $this->review->vehicle->brand . ' ' . $this->review->vehicle->model . ' has been updated.',
            'type' => 'review_updated',
            'created_at' => now()->toISOString(),
        ];
    }
}
