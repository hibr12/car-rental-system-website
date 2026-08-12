<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminCashPaymentInitialized extends Notification
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
        $branchName = $this->payment->branch?->name ?? $this->booking->branch?->name ?? 'your branch';

        return (new MailMessage)
            ->subject('Cash Payment Pending Verification - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A customer cash payment is waiting for verification.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Branch: ' . $branchName)
            ->action('View Booking', url('/api/admin/bookings/' . $this->booking->id))
            ->line('Please verify the cash payment and confirm the booking.');
    }

    public function toArray(object $notifiable): array
    {
        $branchName = $this->payment->branch?->name ?? $this->booking->branch?->name ?? 'your branch';

        return [
            'booking_id' => $this->booking->id,
            'payment_id' => $this->payment->id,
            'booking_reference' => $this->booking->booking_reference,
            'title' => 'Cash Payment Pending Verification',
            'message' => 'Cash payment for booking ' . $this->booking->booking_reference
                . ' is awaiting verification at ' . $branchName . '.',
            'type' => 'admin_cash_payment_initialized',
            'created_at' => now()->toISOString(),
        ];
    }
}

