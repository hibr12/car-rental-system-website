<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Payment $payment
    ) {}
}
