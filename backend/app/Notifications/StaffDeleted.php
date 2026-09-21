<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffDeleted extends Notification
{
    use Queueable;

    public function __construct(
        public int $staffId,
        public string $staffName,
        public ?int $branchId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'staff_id' => $this->staffId,
            'staff_name' => $this->staffName,
            'branch_id' => $this->branchId,
            'title' => 'Staff Member Removed',
            'message' => 'Staff member ' . $this->staffName . ' has been removed from the system.',
            'type' => 'staff_deleted',
            'created_at' => now()->toISOString(),
        ];
    }
}