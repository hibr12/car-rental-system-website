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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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
    }
}
