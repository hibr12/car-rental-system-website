<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentInitialized extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public Payment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Processing - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your payment has been initiated successfully.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Amount: $' . number_format($this->payment->amount, 2))
            ->line('Payment Method: ' . ucfirst(str_replace('_', ' ', $this->payment->payment_method)))
            ->line('Please complete your payment using the secure checkout link.')
            ->line('If you did not initiate this payment, please contact support immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'payment_id' => $this->payment->id,
            'booking_reference' => $this->booking->booking_reference,
            'amount' => $this->payment->amount,
            'title' => 'Payment Initialized',
            'message' => 'Payment of $' . number_format($this->payment->amount, 2) . ' for booking ' . $this->booking->booking_reference . ' has been initiated.',
            'type' => 'payment_initialized',
            'created_at' => now()->toISOString(),
        ];
    }
}
