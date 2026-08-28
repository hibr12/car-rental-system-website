<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminBookingRejected extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Booking Rejected - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A booking has been rejected.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Customer: ' . $this->booking->user->name)
            ->line('Vehicle: ' . $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model);

        if ($this->reason) {
            $mail->line('Reason: ' . $this->reason);
        }

        return $mail
            ->action('View Booking', url('/api/admin/bookings/' . $this->booking->id))
            ->line('The customer has been notified of the rejection.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'customer_name' => $this->booking->user->name,
            'vehicle' => $this->booking->vehicle->brand . ' ' . $this->booking->vehicle->model,
            'reason' => $this->reason,
            'title' => 'Booking Rejected',
            'message' => 'Booking ' . $this->booking->booking_reference . ' has been rejected.'
                . ($this->reason ? ' Reason: ' . $this->reason : ''),
            'type' => 'admin_booking_rejected',
            'created_at' => now()->toISOString(),
        ];
    }
}
