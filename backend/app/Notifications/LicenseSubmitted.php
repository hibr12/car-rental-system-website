<?php

namespace App\Notifications;

use App\Models\DriverLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseSubmitted extends Notification
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
            ->subject('Driver\'s License Submitted for Verification')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your driver\'s license has been submitted for verification.')
            ->line('Our team will review it shortly. You will be notified once the review is complete.')
            ->line('License Category: ' . ucfirst($this->license->license_category))
            ->line('Thank you for completing your profile!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'license_id' => $this->license->id,
            'type'    => 'license_submitted',
            'title'   => 'License Submitted',
            'message' => 'Your driver\'s license has been submitted and is pending verification.',
        ];
    }
}
