<?php

namespace App\Events;

use App\Models\VehicleTransfer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VehicleTransferCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VehicleTransfer $transfer
    ) {}
}