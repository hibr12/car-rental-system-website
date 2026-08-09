<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Invoice;
use App\Models\Inspection;
use App\Models\Maintenance;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\BookingPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\InspectionPolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\UserPolicy;
use App\Policies\VehiclePolicy;
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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Maintenance::class, MaintenancePolicy::class);
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Inspection::class, InspectionPolicy::class);
    }
}
