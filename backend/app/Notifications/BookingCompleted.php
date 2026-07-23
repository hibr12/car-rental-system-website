<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCompleted extends Notification
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
            ->subject('Booking Completed - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your booking has been completed successfully.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model)
            ->line('Rental Period: ' . $this->booking->pickup_date->format('Y-m-d') . ' to ' . $this->booking->return_date->format('Y-m-d'))
            ->line('Total Paid: $' . number_format($this->booking->total_price, 2))
            ->action('Leave a Review', url('/api/vehicles/' . $this->booking->vehicle_id . '/reviews'))
            ->line('We hope you enjoyed your rental experience. Thank you for choosing our service!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'message' => 'Booking ' . $this->booking->booking_reference . ' has been completed successfully.',
            'type' => 'booking_completed',
        ];
    }
}
