<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentApproved extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public Payment $payment,
        public ?User $approver = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approverLabel = $this->approverLabel();

        return (new MailMessage)
            ->subject('Payment Approved - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your payment has been approved by ' . $approverLabel . '.')
            ->line('Booking Reference: ' . $this->booking->booking_reference)
            ->line('Amount: $' . number_format((float) $this->payment->amount, 2))
            ->line('Payment Method: ' . ucfirst(str_replace('_', ' ', $this->payment->payment_method)))
            ->action('View Booking', url('/dashboard/bookings/' . $this->booking->id))
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id'        => $this->booking->id,
            'payment_id'        => $this->payment->id,
            'booking_reference' => $this->booking->booking_reference,
            'amount'            => $this->payment->amount,
            'title'             => 'Payment Approved',
            'message'           => 'Your payment of $' . number_format((float) $this->payment->amount, 2)
                . ' for booking ' . $this->booking->booking_reference
                . ' was approved by ' . $this->approverLabel() . '.',
            'type'              => 'payment_approved',
            'created_at'        => now()->toISOString(),
        ];
    }

    private function approverLabel(): string
    {
        if (!$this->approver) {
            return 'our team';
        }

        if ($this->approver->isBranchManager()) {
            return 'your branch manager';
        }

        if ($this->approver->isAdmin()) {
            return 'an administrator';
        }

        if ($this->approver->isStaff()) {
            return 'branch staff';
        }

        return $this->approver->name;
    }
}
