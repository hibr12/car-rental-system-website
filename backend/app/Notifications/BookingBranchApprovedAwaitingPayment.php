<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingBranchApprovedAwaitingPayment extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $branchName = $this->booking->branch?->name ?? 'the selected branch';

        return (new MailMessage)
            ->subject('Booking Approved - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your booking has been approved by ' . $branchName . '.')
            ->line('Please complete payment to confirm your reservation.')
            ->action('View Booking', url('/api/bookings/' . $this->booking->id))
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        $branchName = $this->booking->branch?->name ?? 'your branch';

        $status = $this->booking->status;
        $detail = match ($status) {
            Booking::STATUS_PAYMENT_REQUIRED, Booking::STATUS_PAYMENT_PROCESSING => 'Complete payment to confirm your reservation.',
            Booking::STATUS_PENDING_ADMIN_APPROVAL => 'Branch approved. Please wait for final confirmation before paying.',
            default => 'Please check your booking status for next steps.',
        };

        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'title' => 'Booking Approved',
            'message' => 'Your booking ' . $this->booking->booking_reference
                . ' has been approved by ' . $branchName . '. ' . $detail,
            'type' => 'booking_branch_approved',
            'created_at' => now()->toISOString(),
        ];
    }
}

