<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\MaintenanceController;
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
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);

    Route::post('/vehicles/{vehicle}/reviews', [ReviewController::class, 'store']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    Route::middleware('role:admin,fleet_manager,staff')->group(function () {
        Route::get('/maintenance', [MaintenanceController::class, 'index']);
        Route::post('/maintenance', [MaintenanceController::class, 'store']);
        Route::get('/maintenance/{maintenance}', [MaintenanceController::class, 'show']);
        Route::put('/maintenance/{maintenance}', [MaintenanceController::class, 'update']);
        Route::delete('/maintenance/{maintenance}', [MaintenanceController::class, 'destroy']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/contact-messages', [ContactMessageController::class, 'index']);
        Route::put('/contact-messages/{contactMessage}', [ContactMessageController::class, 'update']);
        Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy']);
    });

    Route::prefix('admin')->middleware('role:admin,staff')->group(function () {
        Route::get('/bookings', [BookingController::class, 'adminIndex']);
        Route::put('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
        Route::put('/bookings/{booking}/reject', [BookingController::class, 'reject']);
        Route::put('/bookings/{booking}/pickup', [BookingController::class, 'pickup']);
        Route::put('/bookings/{booking}/return', [BookingController::class, 'returnVehicle']);
    });

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{user}', [AdminController::class, 'showUser']);
        Route::put('/users/{user}', [AdminController::class, 'updateUser']);
    });
});

Route::post('/contact-messages', [ContactMessageController::class, 'store']);
