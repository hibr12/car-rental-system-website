<?php

namespace App\Events;

use App\Models\DriverLicense;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DriverLicense $license
    ) {}
}