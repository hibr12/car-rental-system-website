<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccess extends Notification
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
            ->subject('Payment Successful - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your payment has been processed successfully.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Amount Paid: $' . number_format($this->payment->amount, 2))
            ->line('Payment Method: ' . ucfirst(str_replace('_', ' ', $this->payment->payment_method)))
            ->line('Transaction Reference: ' . $this->payment->transaction_reference)
            ->action('View Booking', url('/api/bookings/' . $this->booking->id))
            ->line('Thank you for your payment!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'payment_id' => $this->payment->id,
            'booking_reference' => $this->booking->booking_reference,
            'amount' => $this->payment->amount,
            'title' => 'Payment Successful',
            'message' => 'Payment of $' . number_format($this->payment->amount, 2) . ' for booking ' . $this->booking->booking_reference . ' was successful.',
            'type' => 'payment_success',
            'created_at' => now()->toISOString(),
        ];
    }
}