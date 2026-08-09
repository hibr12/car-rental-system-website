<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPaymentInitialized extends Notification
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
            ->subject('Payment Initialized - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A payment has been initialized for a booking.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Customer: ' . $this->booking->user->name)
            ->line('Amount: $' . number_format($this->payment->amount, 2))
            ->line('Payment Method: ' . ucfirst(str_replace('_', ' ', $this->payment->payment_method)))
            ->line('Transaction Reference: ' . $this->payment->transaction_reference)
            ->action('View Booking', url('/api/admin/bookings/' . $this->booking->id))
            ->line('The customer is completing the payment process.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'payment_id' => $this->payment->id,
            'booking_reference' => $this->booking->booking_reference,
            'customer_name' => $this->booking->user->name,
            'amount' => $this->payment->amount,
            'title' => 'Payment Initialized',
            'message' => 'Payment of $' . number_format($this->payment->amount, 2) . ' initialized for booking ' . $this->booking->booking_reference . '.',
            'type' => 'admin_payment_initialized',
            'created_at' => now()->toISOString(),
        ];
    }
}
