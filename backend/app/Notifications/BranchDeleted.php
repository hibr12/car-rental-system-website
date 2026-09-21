<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchDeleted extends Notification
{
    use Queueable;

    public function __construct(
        public int $branchId,
        public string $branchName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'branch_id' => $this->branchId,
            'branch_name' => $this->branchName,
            'title' => 'Branch Removed',
            'message' => 'Branch ' . $this->branchName . ' has been removed.',
            'type' => 'branch_deleted',
            'created_at' => now()->toISOString(),
        ];
    }
}