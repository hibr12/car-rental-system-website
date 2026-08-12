<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\DriverLicenseController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleDamageController;
use App\Http\Controllers\VehicleDocumentController;
use App\Http\Controllers\VehicleInspectionController;
use App\Http\Controllers\VehicleTransferController;
use Illuminate\Support\Facades\Route;

// ════════════════════════════════════════════════════════════════════
//  PUBLIC ROUTES
// ════════════════════════════════════════════════════════════════════

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/logout',   [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me',        [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::put('/profile',   [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
});

Route::get('/categories',         [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/vehicles',            [VehicleController::class, 'index']);
Route::get('/vehicles/{vehicle}',  [VehicleController::class, 'show']);
Route::get('/vehicles/{vehicle}/reviews', [ReviewController::class, 'index']);

Route::get('/branches',            [BranchController::class, 'index']);
Route::get('/branches/{branch}',   [BranchController::class, 'show']);
Route::get('/branches/{branch}/reviews', [ReviewController::class, 'branchIndex']);

Route::post('/contact-messages',   [ContactMessageController::class, 'store']);

// Chapa payment callback + webhook (no auth — called by Chapa gateway)
Route::match(['get', 'post'], '/payments/callback', [PaymentController::class, 'callback'])
    ->name('payments.callback');
Route::post('/payments/chapa/webhook', [PaymentController::class, 'webhook'])
    ->name('payments.chapa.webhook');

// ════════════════════════════════════════════════════════════════════
//  AUTHENTICATED ROUTES
// ════════════════════════════════════════════════════════════════════

Route::middleware(['auth:sanctum'])->group(function () {

    // ── Notifications ─────────────────────────────────────────────
    Route::prefix('notifications')->group(function () {
        Route::get('/',                         [NotificationController::class, 'index']);
        Route::put('/read-all',                 [NotificationController::class, 'markAllAsRead']);
        Route::get('/{notification}',           [NotificationController::class, 'show']);
        Route::put('/{notification}/read',      [NotificationController::class, 'markAsRead']);
        Route::delete('/{notification}',        [NotificationController::class, 'destroy']);
    });

    // ── Customer: Driver's License ───────────────────────────────
    Route::prefix('customer/license')->group(function () {
        Route::get('/',             [DriverLicenseController::class, 'myLicense']);
        Route::post('/',            [DriverLicenseController::class, 'submit']);
        Route::post('/documents',   [DriverLicenseController::class, 'updateDocuments']);
        Route::get('/eligibility',  [DriverLicenseController::class, 'eligibility']);
    });

    // Secure document serving (customer owns, staff/admin can view).
    Route::get('/licenses/{license}/document/{side}', [DriverLicenseController::class, 'serveDocument'])
        ->where('side', 'front|back');

    // ── Customer: Bookings ────────────────────────────────────────
    Route::get('/bookings/check-availability',  [BookingController::class, 'checkAvailability']);
    Route::get('/bookings/price-estimate',      [BookingController::class, 'priceEstimate']);
    Route::get('/bookings',                     [BookingController::class, 'index']);
    Route::post('/bookings',                    [BookingController::class, 'store']);
    Route::get('/bookings/{booking}',           [BookingController::class, 'show']);
    Route::put('/bookings/{booking}/cancel',    [BookingController::class, 'cancel']);

    // ── Customer: Payments ────────────────────────────────────────
    Route::get('/payments',                     [PaymentController::class, 'index']);
    Route::post('/payments',                    [PaymentController::class, 'store']);
    Route::post('/payments/initialize',         [PaymentController::class, 'initialize']);
    Route::get('/payments/verify/{tx_ref}',     [PaymentController::class, 'verify'])->name('payments.verify');
    Route::get('/payments/{payment}/status',     [PaymentController::class, 'paymentStatus']);
    Route::get('/bookings/{booking}/payment-status', [PaymentController::class, 'bookingPaymentStatus']);
    Route::get('/payments/{payment}',           [PaymentController::class, 'show']);
    Route::post('/payments/{payment}/verify',   [PaymentController::class, 'verifyById'])
        ->middleware('role:admin,branch_manager,staff');

    // ── Customer: Reviews ─────────────────────────────────────────
    Route::get('/reviews',                              [ReviewController::class, 'userReviews']);
    Route::get('/reviews/eligible-bookings',             [ReviewController::class, 'eligibleBookings']);
    Route::get('/reviews/{review}',                     [ReviewController::class, 'show']);
    Route::get('/bookings/{booking}/review-eligibility', [ReviewController::class, 'eligibility']);
    Route::post('/bookings/{booking}/reviews',           [ReviewController::class, 'storeForBooking']);
    Route::post('/vehicles/{vehicle}/reviews',          [ReviewController::class, 'store']);
    Route::put('/reviews/{review}',                     [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}',                  [ReviewController::class, 'destroy']);

    // ═══════════════════════════════════════════════════════════════
    //  MANAGEMENT ROUTES (admin + branch_manager + fleet_manager + staff)
    // ═══════════════════════════════════════════════════════════════

    // ── Categories (admin / fleet_manager) ───────────────────────
    Route::middleware('role:admin,fleet_manager,branch_manager')->group(function () {
        Route::post('/categories',               [CategoryController::class, 'store']);
        Route::put('/categories/{category}',     [CategoryController::class, 'update']);
    });
    Route::delete('/categories/{category}',      [CategoryController::class, 'destroy'])
        ->middleware('role:admin');

    // ── Vehicles (admin / fleet_manager / branch_manager) ─────────
    Route::middleware('role:admin,fleet_manager,branch_manager')->group(function () {
        Route::post('/vehicles',                 [VehicleController::class, 'store']);
        Route::put('/vehicles/{vehicle}',        [VehicleController::class, 'update']);
    });
    Route::delete('/vehicles/{vehicle}',         [VehicleController::class, 'destroy'])
        ->middleware('role:admin');

    // ── Vehicle Transfers ─────────────────────────────────────────
    Route::prefix('vehicle-transfers')->middleware('role:admin,branch_manager,fleet_manager')->group(function () {
        Route::get('/',                          [VehicleTransferController::class, 'index']);
        Route::post('/',                         [VehicleTransferController::class, 'store']);
        Route::get('/{transfer}',                [VehicleTransferController::class, 'show']);
        Route::put('/{transfer}/approve',        [VehicleTransferController::class, 'approve'])->middleware('role:admin,branch_manager');
        Route::put('/{transfer}/reject',         [VehicleTransferController::class, 'reject'])->middleware('role:admin,branch_manager');
        Route::put('/{transfer}/cancel',        [VehicleTransferController::class, 'cancel'])->middleware('role:admin,branch_manager');
        Route::put('/{transfer}/in-transit',     [VehicleTransferController::class, 'markInTransit']);
        Route::put('/{transfer}/complete',       [VehicleTransferController::class, 'complete']);
        Route::put('/{transfer}/execute',        [VehicleTransferController::class, 'executeNow'])->middleware('role:admin');
        Route::get('/{transfer}/history',      [VehicleTransferController::class, 'history']);
    });

    // ── Maintenance ───────────────────────────────────────────────
    Route::prefix('maintenance')->middleware('role:admin,fleet_manager,branch_manager,staff')->group(function () {
        Route::get('/',                          [MaintenanceController::class, 'index']);
        Route::post('/',                         [MaintenanceController::class, 'store']);
        Route::get('/{maintenance}',             [MaintenanceController::class, 'show']);
        Route::put('/{maintenance}',             [MaintenanceController::class, 'update']);
        Route::delete('/{maintenance}',          [MaintenanceController::class, 'destroy']);
    });

    // ── Fleet lifecycle: inspections, documents, damage ─────────
    Route::prefix('vehicle-inspections')->middleware('role:admin,fleet_manager,branch_manager,staff')->group(function () {
        Route::get('/',                          [VehicleInspectionController::class, 'index']);
        Route::post('/',                         [VehicleInspectionController::class, 'store']);
        Route::get('/{inspection}',              [VehicleInspectionController::class, 'show']);
        Route::put('/{inspection}/complete',     [VehicleInspectionController::class, 'complete']);
    });

    Route::prefix('vehicle-documents')->middleware('role:admin,fleet_manager,branch_manager')->group(function () {
        Route::get('/',                          [VehicleDocumentController::class, 'index']);
        Route::post('/',                         [VehicleDocumentController::class, 'store']);
        Route::put('/{document}',                [VehicleDocumentController::class, 'update']);
        Route::delete('/{document}',             [VehicleDocumentController::class, 'destroy']);
    });

    Route::prefix('vehicle-damages')->middleware('role:admin,fleet_manager,branch_manager,staff')->group(function () {
        Route::get('/',                          [VehicleDamageController::class, 'index']);
        Route::post('/',                         [VehicleDamageController::class, 'store']);
        Route::put('/{damage}',                  [VehicleDamageController::class, 'update']);
    });

    // ── Rentals & Check-in/Check-out ─────────────────────────────
    Route::prefix('rentals')->group(function () {
        Route::get('/',                          [RentalController::class, 'index']);
        Route::put('/{booking}/checkout',        [RentalController::class, 'checkOut'])
            ->middleware('role:admin,branch_manager,staff');
        Route::put('/{booking}/checkin',         [RentalController::class, 'checkIn'])
            ->middleware('role:admin,branch_manager,staff');
    });

    // ── Staff Management ──────────────────────────────────────────
    Route::prefix('staff')->middleware('role:admin,branch_manager')->group(function () {
        Route::get('/',                          [StaffController::class, 'index']);
        Route::post('/',                         [StaffController::class, 'store']);
        Route::put('/{user}',                    [StaffController::class, 'update']);
        Route::delete('/{user}',                 [StaffController::class, 'destroy']);
    });

    // ── Contact Messages (admin only) ─────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/contact-messages',           [ContactMessageController::class, 'index']);
        Route::put('/contact-messages/{contactMessage}', [ContactMessageController::class, 'update']);
        Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy']);
    });

    // ═══════════════════════════════════════════════════════════════
    //  ADMIN-ONLY ROUTES
    // ═══════════════════════════════════════════════════════════════

    Route::prefix('admin')->middleware('role:admin')->group(function () {

        // Dashboard
        Route::get('/dashboard',                 [AdminController::class, 'dashboard']);

        // Company
        Route::get('/company',                   [CompanyController::class, 'show']);
        Route::put('/company',                   [CompanyController::class, 'update']);

        // Branches
        Route::get('/branches',                  [BranchController::class, 'index']);
        Route::post('/branches',                 [BranchController::class, 'store']);
        Route::get('/branches/{branch}',         [BranchController::class, 'show']);
        Route::put('/branches/{branch}',         [BranchController::class, 'update']);
        Route::put('/branches/{branch}/activate',   [BranchController::class, 'activate']);
        Route::put('/branches/{branch}/deactivate', [BranchController::class, 'deactivate']);
        Route::get('/branches/{branch}/dashboard',  [BranchController::class, 'dashboard']);
        Route::get('/branches/{branch}/vehicles',   [BranchController::class, 'vehicles']);
        Route::get('/branches/{branch}/staff',      [BranchController::class, 'staff']);
        Route::get('/branches/{branch}/bookings',   [BranchController::class, 'bookings']);
        Route::get('/branches/{branch}/payments',   [BranchController::class, 'payments']);

        // Users
        Route::get('/users',                     [AdminController::class, 'users']);
        Route::get('/users/{user}',              [AdminController::class, 'showUser']);
        Route::put('/users/{user}',              [AdminController::class, 'updateUser']);

        // Reports
        Route::get('/reports/revenue',           [ReportController::class, 'companyRevenue']);
        Route::get('/reports/fleet',             [ReportController::class, 'fleetUtilization']);
    });

    Route::prefix('admin')->middleware('role:admin,branch_manager')->group(function () {
        Route::get('/reviews',                   [ReviewController::class, 'adminIndex']);
        Route::get('/reviews/stats',             [ReviewController::class, 'adminStats']);
        Route::patch('/reviews/{review}/status', [ReviewController::class, 'updateStatus']);
        Route::post('/reviews/{review}/respond', [ReviewController::class, 'respond']);
    });

    // ═══════════════════════════════════════════════════════════════
    //  ADMIN + STAFF BOOKING MANAGEMENT
    // ═══════════════════════════════════════════════════════════════

    // ── Admin + Staff: Driver's License Review ────────────────────
    Route::prefix('admin')->middleware('role:admin,branch_manager,staff')->group(function () {
        Route::get('/licenses',                    [DriverLicenseController::class, 'index']);
        Route::get('/licenses/{license}',          [DriverLicenseController::class, 'show']);
        Route::post('/licenses/{license}/approve', [DriverLicenseController::class, 'approve']);
        Route::post('/licenses/{license}/reject',  [DriverLicenseController::class, 'reject']);
    });

    Route::prefix('admin')->middleware('role:admin,branch_manager,staff')->group(function () {
        Route::get('/bookings',                  [BookingController::class, 'adminIndex']);
        Route::put('/bookings/{booking}/confirm',[BookingController::class, 'confirm']);
        Route::put('/bookings/{booking}/reject', [BookingController::class, 'reject']);
        Route::put('/bookings/{booking}/prepare-pickup', [BookingController::class, 'preparePickup']);
        Route::put('/bookings/{booking}/pickup', [BookingController::class, 'pickup']);
        Route::put('/bookings/{booking}/return', [BookingController::class, 'returnVehicle']);

        Route::put('/payments/{payment}/fail',   [PaymentController::class, 'markAsFailed']);
        Route::put('/payments/{payment}/refund', [PaymentController::class, 'refund']);
        Route::post('/payments/{payment}/confirm-cash', [PaymentController::class, 'confirmCash']);
        Route::get('/payment-history',           [PaymentController::class, 'history']);
        Route::get('/payments/reconciliation',   [PaymentController::class, 'reconciliation']);
    });

    // Payment attempts — customer + management
    Route::get('/bookings/{booking}/payment-attempts', [PaymentController::class, 'bookingAttempts']);

    // Archive — admin only; soft-archive, never hard-delete financial records
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/archive/bookings',              [ArchiveController::class, 'bookings']);
        Route::put('/bookings/{booking}/archive',    [ArchiveController::class, 'archiveBooking']);
        Route::get('/archive/payments',               [ArchiveController::class, 'payments']);
        Route::put('/payments/{payment}/archive',    [ArchiveController::class, 'archivePayment']);
    });

    // ═══════════════════════════════════════════════════════════════
    //  BRANCH MANAGER PORTAL
    // ═══════════════════════════════════════════════════════════════

    Route::prefix('branch')->middleware('role:branch_manager,admin')->group(function () {
        Route::get('/dashboard',                 [BranchController::class, 'branchManagerDashboard']);
        Route::get('/customers',                 [BranchController::class, 'branchCustomers']);
        Route::get('/reports',                   [ReportController::class, 'branchReport']);
        Route::get('/reports/fleet',             [ReportController::class, 'fleetUtilization']);
    });

    Route::prefix('maintenance-requests')->group(function () {
        Route::get('/',                          [MaintenanceRequestController::class, 'index'])
            ->middleware('role:admin,branch_manager,fleet_manager');
        Route::post('/',                         [MaintenanceRequestController::class, 'store'])
            ->middleware('role:admin,branch_manager');
        Route::put('/{maintenanceRequest}/approve', [MaintenanceRequestController::class, 'approve'])
            ->middleware('role:admin,fleet_manager');
        Route::put('/{maintenanceRequest}/reject', [MaintenanceRequestController::class, 'reject'])
            ->middleware('role:admin,fleet_manager');
    });

    // ═══════════════════════════════════════════════════════════════
    //  REPORTS (admin + branch_manager)
    // ═══════════════════════════════════════════════════════════════

    Route::prefix('reports')->middleware('role:admin,branch_manager,fleet_manager')->group(function () {
        Route::get('/branch',                    [ReportController::class, 'branchReport']);
        Route::get('/fleet',                     [ReportController::class, 'fleetUtilization']);
    });

    Route::prefix('fleet')->middleware('role:admin,fleet_manager')->group(function () {
        Route::get('/dashboard',                 [FleetController::class, 'dashboard']);
    });

    Route::get('/reports/revenue', [ReportController::class, 'companyRevenue'])
        ->middleware(['role:admin']);

});
