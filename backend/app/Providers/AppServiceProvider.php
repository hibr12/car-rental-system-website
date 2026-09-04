<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\DriverLicense;
use App\Models\Maintenance;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\BookingPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\DriverLicensePolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\UserPolicy;
use App\Policies\VehiclePolicy;
use App\Services\ChapaConfigValidator;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Rate Limiters
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts. Please try again in 1 minute.',
                ], 429));
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'success' => false,
                    'message' => 'Too many registration attempts. Please try again in 1 minute.',
                ], 429));
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(2)
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'success' => false,
                    'message' => 'Too many password reset requests. Please try again in 1 minute.',
                ], 429));
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn () => response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.',
                ], 429));
        });

        // Validate Chapa configuration at boot so misconfigured environments
        // fail loudly instead of silently using wrong credentials.
        // In 'live' mode, missing or test-looking keys throw immediately.
        // Skip during unit tests (RefreshDatabase truncates config each run).
        if (!$this->app->runningUnitTests()) {
            app(ChapaConfigValidator::class)->validate();
        }

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Maintenance::class, MaintenancePolicy::class);
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);
        Gate::policy(DriverLicense::class, DriverLicensePolicy::class);

        // Customize email verification URL to point to frontend
VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Verify Email Address')
                ->line('Please click the button below to verify your email address.')
                ->action('Verify Email Address', $url)
                ->line('If you did not create an account, no further action is required.');
        });
    }
}
