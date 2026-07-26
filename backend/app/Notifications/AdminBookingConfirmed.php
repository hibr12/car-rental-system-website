<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminBookingConfirmed extends Notification
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
        return (new MailMessage)
            ->subject('Booking Confirmed - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A booking has been confirmed.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Customer: ' . $this->booking->user->name)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model)
            ->line('Pickup: ' . $this->booking->pickup_date->format('Y-m-d H:i'))
            ->line('Return: ' . $this->booking->return_date->format('Y-m-d H:i'))
            ->line('Total Price: $' . number_format($this->booking->total_price, 2))
            ->action('View Booking', url('/api/admin/bookings/' . $this->booking->id))
            ->line('The customer has been notified to proceed with payment.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'customer_name' => $this->booking->user->name,
            'vehicle' => $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model,
            'total_price' => $this->booking->total_price,
            'title' => 'Booking Confirmed',
            'message' => 'Booking ' . $this->booking->booking_reference . ' has been confirmed.',
            'type' => 'admin_booking_confirmed',
            'created_at' => now()->toISOString(),
        ];
    }
}
