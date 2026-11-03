<?php

use App\Models\Booking;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:ensure-initial-admin', function () {
    $email = trim((string) getenv('INITIAL_ADMIN_EMAIL'));
    $password = (string) getenv('INITIAL_ADMIN_PASSWORD');

    if ($email === '' && $password === '') {
        $this->info('Initial admin credentials are not configured; skipping.');

        return Command::SUCCESS;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('INITIAL_ADMIN_EMAIL must be a valid email address.');

        return Command::FAILURE;
    }

    if (strlen($password) < 12) {
        $this->error('INITIAL_ADMIN_PASSWORD must contain at least 12 characters.');

        return Command::FAILURE;
    }

    $admin = User::where('email', $email)->first();

    if (!$admin) {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_COMPANY_ADMIN,
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();

        $this->info('Initial administrator created.');

        return Command::SUCCESS;
    }

    if (!$admin->isAdmin()) {
        $admin->forceFill([
            'password' => $password,
            'role' => User::ROLE_COMPANY_ADMIN,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ])->save();

        $this->info('Existing user promoted to administrator with the configured password.');

        return Command::SUCCESS;
    }

    $this->info('Initial administrator already exists; its password was preserved.');

    return Command::SUCCESS;
})->purpose('Create the first production administrator from environment secrets');

// Nobody is guaranteed to ever re-open an abandoned checkout, so this sweep
// is what actually expires its stale payment and frees the booking/vehicle
// hold — reconcileStalePaymentState() is otherwise only triggered reactively
// when a customer happens to read or re-initialize that specific booking.
Schedule::call(function () {
    $paymentService = app(PaymentService::class);

    Booking::where('status', Booking::STATUS_PAYMENT_PROCESSING)
        ->each(fn (Booking $booking) => $paymentService->reconcileStalePaymentState($booking));
})->everyFifteenMinutes()->name('reconcile-stale-payments')->withoutOverlapping();
