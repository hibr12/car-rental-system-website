<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejected extends Notification
{
    use Queueable;

    public Booking $booking;
    public ?string $reason;

    public function __construct(Booking $booking, ?string $reason = null)
    {
        $this->booking = $booking;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Booking Rejected - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Unfortunately, your booking has been rejected.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model);

        if ($this->reason) {
            $mail->line('Reason: ' . $this->reason);
        }

        return $mail
            ->action('View Details', url('/api/bookings/' . $this->booking->id))
            ->line('If you have any questions, please contact support.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'reason' => $this->reason,
            'message' => 'Booking ' . $this->booking->booking_reference . ' has been rejected.'
                . ($this->reason ? ' Reason: ' . $this->reason : ''),
            'type' => 'booking_rejected',
        ];
    }
}
