<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseVerified extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $status,
        public ?string $notes = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusText = $this->status === 'verified' ? 'verified' : 'rejected';

        return (new MailMessage)
            ->subject("Driver's License {$statusText}")
            ->line("Your driver's license has been {$statusText}.")
            ->when($this->notes, fn($m) => $m->line("Notes: {$this->notes}"))
            ->action('View Profile', url('/dashboard/profile'))
            ->line('Thank you for using our service!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'type' => 'license_' . $this->status,
            'title' => 'Driver\'s License ' . ucfirst($this->status),
            'message' => $this->status === 'verified'
                ? 'Your driver\'s license has been verified successfully.'
                : 'Your driver\'s license could not be verified. Please upload a valid document.',
            'related_type' => User::class,
            'related_id' => $this->user->id,
        ];
    }
}
