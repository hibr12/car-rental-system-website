<?php

namespace App\Notifications;

use App\Models\DriverLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminLicenseSubmitted extends Notification
{
    use Queueable;

    public function __construct(public DriverLicense $license) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $customerName = $this->license->user?->name ?? $this->license->full_name;

        return (new MailMessage)
            ->subject('New Driver\'s License Submitted for Review')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A customer has submitted a driver\'s license for verification.')
            ->line('Customer: ' . $customerName)
            ->line('License Category: ' . ucfirst($this->license->license_category))
            ->line('Submitted: ' . ($this->license->submitted_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i')))
            ->action('Review License', url('/admin/licenses'))
            ->line('Please review and approve or reject the submission.');
    }

    public function toArray(object $notifiable): array
    {
        $customerName = $this->license->user?->name ?? $this->license->full_name;

        return [
            'license_id'    => $this->license->id,
            'customer_name' => $customerName,
            'type'          => 'admin_license_submitted',
            'title'         => 'License Submitted for Review',
            'message'       => $customerName . ' submitted a driver\'s license for verification.',
            'created_at'    => now()->toISOString(),
        ];
    }
}
