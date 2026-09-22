<?php

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nobody is guaranteed to ever re-open an abandoned checkout, so this sweep
// is what actually expires its stale payment and frees the booking/vehicle
// hold — reconcileStalePaymentState() is otherwise only triggered reactively
// when a customer happens to read or re-initialize that specific booking.
Schedule::call(function () {
    $paymentService = app(PaymentService::class);

    Booking::where('status', Booking::STATUS_PAYMENT_PROCESSING)
        ->each(fn (Booking $booking) => $paymentService->reconcileStalePaymentState($booking));
})->everyFifteenMinutes()->name('reconcile-stale-payments')->withoutOverlapping();
