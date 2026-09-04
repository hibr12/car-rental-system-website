<?php

namespace App\Notifications;

use App\Models\DriverLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiringSoon extends Notification
{
    use Queueable;

    public function __construct(public DriverLicense $license, public int $daysRemaining) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Action Required: Driver's License Expiring in {$this->daysRemaining} Days")
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("Your driver's license expires in **{$this->daysRemaining} days** ({$this->license->expiry_date?->format('d M Y')}).")
            ->line('Please upload your renewed license to continue renting vehicles after the expiration date.')
            ->action('Update License', rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') . '/dashboard/license');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'license_id'     => $this->license->id,
            'type'           => 'license_expiring_soon',
            'title'          => "License Expiring in {$this->daysRemaining} Days",
            'message'        => "Your driver's license expires in {$this->daysRemaining} day(s). Please upload your renewed license.",
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
