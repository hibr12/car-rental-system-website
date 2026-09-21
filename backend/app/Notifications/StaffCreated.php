<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffCreated extends Notification
{
    use Queueable;

    public function __construct(
        public User $staff
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'staff_id' => $this->staff->id,
            'staff_name' => $this->staff->name,
            'staff_email' => $this->staff->email,
            'role' => $this->staff->role,
            'branch_id' => $this->staff->branch_id,
            'branch_name' => $this->staff->branch?->name,
            'title' => 'Staff Member Added',
            'message' => 'New staff member ' . $this->staff->name . ' (' . $this->staff->role . ') has been added to ' . ($this->staff->branch?->name ?? 'the system') . '.',
            'type' => 'staff_created',
            'created_at' => now()->toISOString(),
        ];
    }
}