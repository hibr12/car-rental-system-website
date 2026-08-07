<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification
{
    use Queueable;

    public Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking Cancelled - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your booking has been cancelled.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->action('View Details', url('/api/bookings/' . $this->booking->id))
            ->line('If you have any questions, please contact support.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'message' => 'Booking ' . $this->booking->booking_reference . ' has been cancelled.',
            'type' => 'booking_cancelled',
        ];
    }
}