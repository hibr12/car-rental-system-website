<?php

namespace App\Notifications;

use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchCreated extends Notification
{
    use Queueable;

    public function __construct(
        public Branch $branch
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'branch_id' => $this->branch->id,
            'branch_name' => $this->branch->name,
            'branch_code' => $this->branch->code,
            'city' => $this->branch->city,
            'title' => 'Branch Created',
            'message' => 'New branch ' . $this->branch->name . ' (' . $this->branch->code . ') has been created in ' . $this->branch->city . '.',
            'type' => 'branch_created',
            'created_at' => now()->toISOString(),
        ];
    }
}