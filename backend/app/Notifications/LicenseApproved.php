<?php

namespace App\Notifications;

use App\Models\DriverLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseApproved extends Notification
{
    use Queueable;

    public function __construct(public DriverLicense $license) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Driver\'s License Verified ✓')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Great news! Your driver\'s license has been verified.')
            ->line('You are now eligible to book vehicles on Apex Rentals.')
            ->line('License Expiry: ' . $this->license->expiry_date?->format('d M Y'))
            ->action('Book a Vehicle', rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') . '/vehicles');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'license_id' => $this->license->id,
            'type'    => 'license_approved',
            'title'   => 'License Verified',
            'message' => 'Your driver\'s license has been verified. You can now book eligible vehicles.',
        ];
    }
}
