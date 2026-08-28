<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminBookingPickedUp extends Notification
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
            ->subject('Vehicle Picked Up - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A vehicle has been picked up for an active rental.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Customer: ' . $this->booking->user->name)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model)
            ->line('Pickup Location: ' . $this->booking->pickup_location)
            ->line('Expected Return: ' . $this->booking->return_date->format('Y-m-d H:i'))
            ->action('View Booking', url('/api/admin/bookings/' . $this->booking->id))
            ->line('The rental period is now active.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'customer_name' => $this->booking->user->name,
            'vehicle' => $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model,
            'title' => 'Vehicle Picked Up',
            'message' => 'Vehicle for booking ' . $this->booking->booking_reference . ' has been picked up.',
            'type' => 'admin_booking_picked_up',
            'created_at' => now()->toISOString(),
        ];
    }
}
