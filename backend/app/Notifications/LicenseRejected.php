<?php

namespace App\Notifications;

use App\Models\DriverLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseRejected extends Notification
{
    use Queueable;

    public function __construct(public DriverLicense $license, public string $reason) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Driver\'s License Verification — Action Required')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Unfortunately, we were unable to verify your driver\'s license.')
            ->line('**Reason:** ' . $this->reason)
            ->line('Please upload a new, clear image of your license and resubmit.')
            ->action('Update License', rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') . '/dashboard/license');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'license_id' => $this->license->id,
            'type'    => 'license_rejected',
            'title'   => 'License Verification Rejected',
            'message' => 'Your driver\'s license could not be verified. Reason: ' . $this->reason,
            'reason'  => $this->reason,
        ];
    }
}
