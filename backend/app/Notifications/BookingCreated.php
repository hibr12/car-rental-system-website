<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCreated extends Notification
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
            ->subject('Booking Created - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your booking has been created successfully.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model)
            ->line('Pickup: ' . $this->booking->pickup_date->format('Y-m-d H:i'))
            ->line('Return: ' . $this->booking->return_date->format('Y-m-d H:i'))
            ->line('Total Price: $' . number_format($this->booking->total_price, 2))
            ->action('View Booking', url('/api/bookings/' . $this->booking->id))
            ->line('Thank you for choosing our service!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'message' => 'Booking ' . $this->booking->booking_reference . ' created successfully.',
            'type' => 'booking_created',
        ];
    }
}