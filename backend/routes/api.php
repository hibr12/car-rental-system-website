<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/vehicles', [VehicleController::class, 'index']);
Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
Route::get('/vehicles/{vehicle}/reviews', [ReviewController::class, 'index']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin,fleet_manager');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:admin,fleet_manager');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role:admin');

    Route::post('/vehicles', [VehicleController::class, 'store'])->middleware('role:admin,fleet_manager');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->middleware('role:admin,fleet_manager');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->middleware('role:admin');

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/check-availability', [BookingController::class, 'checkAvailability']);
    Route::get('/bookings/price-estimate', [BookingController::class, 'priceEstimate']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/payments/initialize', [PaymentController::class, 'initialize']);
    Route::get('/payments/verify/{tx_ref}', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);

    Route::post('/payments/callback', [PaymentController::class, 'callback'])->name('payments.callback')->withoutMiddleware('auth:sanctum');

    Route::get('/reviews', [ReviewController::class, 'userReviews']);
    Route::post('/vehicles/{vehicle}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    Route::prefix('admin')->middleware('role:admin,staff')->group(function () {
        Route::get('/bookings', [BookingController::class, 'adminIndex']);
        Route::put('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
        Route::put('/bookings/{booking}/reject', [BookingController::class, 'reject']);
        Route::put('/bookings/{booking}/pickup', [BookingController::class, 'pickup']);
        Route::put('/bookings/{booking}/return', [BookingController::class, 'returnVehicle']);

        Route::put('/payments/{payment}/fail', [PaymentController::class, 'markAsFailed']);
        Route::put('/payments/{payment}/refund', [PaymentController::class, 'refund']);
    });
});