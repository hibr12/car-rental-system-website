<?php

namespace App\Notifications;

use App\Models\DriverLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DriverLicense $license
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'license_id' => $this->license->id,
            'user_id' => $this->license->user_id,
            'customer_name' => $this->license->user->name,
            'document_type' => $this->license->document_type,
            'document_number' => $this->license->document_number,
            'license_category' => $this->license->license_category,
            'status' => $this->license->status,
            'title' => 'Document Submitted for Verification',
            'message' => $this->license->user->name . ' has submitted a ' . $this->license->getDocumentTypeDisplayName() . ' for verification.',
            'type' => 'license_submitted',
            'created_at' => now()->toISOString(),
        ];
    }
}