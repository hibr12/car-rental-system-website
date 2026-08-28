<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingPickupReminder extends Notification
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
            ->subject('Vehicle Pickup Confirmation - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your vehicle has been picked up successfully.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model)
            ->line('Pickup Location: ' . $this->booking->pickup_location)
            ->line('Expected Return: ' . $this->booking->return_date->format('Y-m-d H:i'))
            ->action('View Booking', url('/api/bookings/' . $this->booking->id))
            ->line('Please return the vehicle on time. Thank you for choosing our service!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'title' => 'Vehicle Picked Up',
            'message' => 'Vehicle for booking ' . $this->booking->booking_reference . ' has been picked up.',
            'type' => 'booking_picked_up',
            'created_at' => now()->toISOString(),
        ];
    }
}
