<?php

namespace App\Notifications;

use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public Branch $branch,
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
            'branch_id' => $this->branch->id,
            'branch_name' => $this->branch->name,
            'branch_code' => $this->branch->code,
            'city' => $this->branch->city,
            'changes' => $this->changes,
            'title' => 'Branch Updated',
            'message' => 'Branch ' . $this->branch->name . ' has been updated. Changes: ' . $changeList,
            'type' => 'branch_updated',
            'created_at' => now()->toISOString(),
        ];
    }
}