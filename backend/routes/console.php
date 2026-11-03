<?php

use App\Models\Booking;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:reconcile-legacy-migrations', function () {
    if (!Schema::hasTable('migrations')) {
        $this->info('Migration table does not exist yet; no legacy records to reconcile.');

        return Command::SUCCESS;
    }

    $legacyName = '2026_08_11_082853_create_companies_table';
    $currentName = '2026_08_08_000000_create_companies_table';

    if (DB::table('migrations')->where('migration', $currentName)->exists()) {
        $this->info('Current companies migration record already exists.');

        return Command::SUCCESS;
    }

    $updated = DB::table('migrations')
        ->where('migration', $legacyName)
        ->update(['migration' => $currentName]);

    $this->info($updated
        ? 'Reconciled the legacy companies migration record.'
        : 'No legacy companies migration record required reconciliation.');

    return Command::SUCCESS;
})->purpose('Reconcile migration filenames used by earlier production deployments');

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
