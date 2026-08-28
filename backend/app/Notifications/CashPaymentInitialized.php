<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CashPaymentInitialized extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking, public Payment $payment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $branchName = $this->payment->branch?->name
            ?? $this->booking->branch?->name
            ?? 'your selected branch';

        return (new MailMessage)
            ->subject('Cash Payment Pending Verification - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your cash payment is waiting for verification.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Pay at: ' . $branchName)
            ->line('Amount Due: $' . number_format((float) $this->payment->amount, 2))
            ->action('View Booking', url('/api/bookings/' . $this->booking->id))
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        $branchName = $this->payment->branch?->name
            ?? $this->booking->branch?->name
            ?? 'your selected branch';

        return [
            'booking_id' => $this->booking->id,
            'payment_id' => $this->payment->id,
            'booking_reference' => $this->booking->booking_reference,
            'title' => 'Cash Payment Waiting for Verification',
            'message' => 'Your cash payment for booking ' . $this->booking->booking_reference
                . ' is waiting for branch verification at ' . $branchName . '.',
            'type' => 'cash_payment_initialized',
            'created_at' => now()->toISOString(),
        ];
    }
}

