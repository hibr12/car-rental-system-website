<?php

namespace App\Events;

use App\Models\DriverLicense;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DriverLicense $license,
        public string $reason
    ) {}
}