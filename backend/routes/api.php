<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
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

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin,fleet_manager');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:admin,fleet_manager');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role:admin');

    Route::post('/vehicles', [VehicleController::class, 'store'])->middleware('role:admin,fleet_manager');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->middleware('role:admin,fleet_manager');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->middleware('role:admin');
});
