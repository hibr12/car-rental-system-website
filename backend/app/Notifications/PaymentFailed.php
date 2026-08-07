<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification
{
    use Queueable;

    public Booking $booking;
    public Payment $payment;

    public function __construct(Booking $booking, Payment $payment)
    {
        $this->booking = $booking;
        $this->payment = $payment;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Failed - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Unfortunately, your payment has failed.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Amount: $' . number_format($this->payment->amount, 2))
            ->line('Please try again or use a different payment method.')
            ->action('View Booking', url('/api/bookings/' . $this->booking->id))
            ->line('If you need assistance, please contact support.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'payment_id' => $this->payment->id,
            'booking_reference' => $this->booking->booking_reference,
            'amount' => $this->payment->amount,
            'message' => 'Payment of $' . number_format($this->payment->amount, 2) . ' for booking ' . $this->booking->booking_reference . ' has failed.',
            'type' => 'payment_failed',
        ];
    }
}