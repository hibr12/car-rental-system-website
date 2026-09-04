<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReminder extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
        $reviewUrl = $frontendUrl . '/dashboard/bookings/' . $this->booking->id . '/review';

        return (new MailMessage)
            ->subject('Reminder: Rate Your Apex Rentals Experience')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('We noticed you have not yet reviewed your recent rental.')
            ->line('Booking: ' . $this->booking->booking_reference)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model)
            ->action('Rate Your Rental', $reviewUrl)
            ->line('Your feedback helps us improve our service.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'title' => 'Review Reminder',
            'message' => 'How was your rental experience? Share your feedback.',
            'type' => 'review_reminder',
            'action_url' => '/dashboard/bookings/' . $this->booking->id . '/review',
            'action_label' => 'Rate Your Rental',
            'created_at' => now()->toISOString(),
        ];
    }
}
