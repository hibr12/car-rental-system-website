<?php

namespace App\Events;

use App\Models\Maintenance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Maintenance $maintenance
    ) {}
}