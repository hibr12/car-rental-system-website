<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $staffId,
        public string $staffName,
        public ?int $branchId
    ) {}
}