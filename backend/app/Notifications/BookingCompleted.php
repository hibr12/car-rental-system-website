<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCompleted extends Notification
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
            ->subject('Rental Complete - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your rental is complete. How was your experience?')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model)
            ->line('Rental Period: ' . $this->booking->pickup_date->format('M j, Y') . ' to ' . $this->booking->return_date->format('M j, Y'))
            ->action('Rate Your Rental', $reviewUrl)
            ->line('Thank you for choosing Apex Rentals!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'title' => 'Rental Complete',
            'message' => 'Your rental is complete. How was your experience?',
            'type' => 'booking_completed',
            'action_url' => '/dashboard/bookings/' . $this->booking->id . '/review',
            'action_label' => 'Rate Your Rental',
            'created_at' => now()->toISOString(),
        ];
    }
}
