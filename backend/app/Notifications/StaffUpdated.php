<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public User $staff,
        public array $changes
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $changeList = implode(', ', array_keys($this->changes));
        return [
            'staff_id' => $this->staff->id,
            'staff_name' => $this->staff->name,
            'staff_email' => $this->staff->email,
            'role' => $this->staff->role,
            'branch_id' => $this->staff->branch_id,
            'branch_name' => $this->staff->branch?->name,
            'changes' => $this->changes,
            'title' => 'Staff Member Updated',
            'message' => 'Staff member ' . $this->staff->name . ' has been updated. Changes: ' . $changeList,
            'type' => 'staff_updated',
            'created_at' => now()->toISOString(),
        ];
    }
}