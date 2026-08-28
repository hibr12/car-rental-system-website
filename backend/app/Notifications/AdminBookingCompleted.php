<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminBookingCompleted extends Notification
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
            ->subject('Booking Completed - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A booking has been completed successfully.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Customer: ' . $this->booking->user->name)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model)
            ->line('Rental Period: ' . $this->booking->pickup_date->format('Y-m-d') . ' to ' . $this->booking->return_date->format('Y-m-d'))
            ->line('Total: $' . number_format($this->booking->total_price, 2))
            ->action('View Booking', url('/api/admin/bookings/' . $this->booking->id))
            ->line('The vehicle has been returned to available inventory.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'customer_name' => $this->booking->user->name,
            'vehicle' => $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model,
            'total_price' => $this->booking->total_price,
            'title' => 'Booking Completed',
            'message' => 'Booking ' . $this->booking->booking_reference . ' has been completed.',
            'type' => 'admin_booking_completed',
            'created_at' => now()->toISOString(),
        ];
    }
}
