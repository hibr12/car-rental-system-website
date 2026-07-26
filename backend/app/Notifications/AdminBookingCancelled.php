<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminBookingCancelled extends Notification
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
            ->subject('Booking Cancelled - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A booking has been cancelled.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Customer: ' . $this->booking->user->name)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model)
            ->action('View Booking', url('/api/admin/bookings/' . $this->booking->id))
            ->line('The vehicle has been released back to available inventory.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'customer_name' => $this->booking->user->name,
            'vehicle' => $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model,
            'title' => 'Booking Cancelled',
            'message' => 'Booking ' . $this->booking->booking_reference . ' has been cancelled.',
            'type' => 'admin_booking_cancelled',
            'created_at' => now()->toISOString(),
        ];
    }
}
