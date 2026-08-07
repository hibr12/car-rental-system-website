# Car Rental System Backend

## Project Overview

This Laravel backend powers the car rental system with authentication, vehicle browsing, booking workflows, payments, reviews, maintenance tracking, and admin operations.

## Current Focus

The backend is currently being extended for the admin-maintenance scope, with a strong emphasis on admin operations, maintenance handling, reporting, and deployment readiness.

## Features

- Laravel Sanctum authentication
- Role-based access for customers, admins, fleet managers, and staff
- Vehicle and category management
- Booking lifecycle management
- Review and payment handling
- Maintenance and contact-message administration
- Dashboard statistics and reporting endpoints

## Local Development Setup

1. Install PHP dependencies with composer install
2. Copy .env.example to .env and configure your database values
3. Run php artisan migrate --seed
4. Start the app with php artisan serve

## Testing

Run the backend test suite with:

- php artisan test

For the admin-focused verification flow:

- php artisan test --filter=AdminMaintenanceTest
- php artisan test --filter=BookingTest

## Deployment Notes

- Ensure the environment variables are set for the production database and app URL
- Generate an application key with php artisan key:generate
- Configure CORS and trusted origins for the frontend domain
- Make sure storage links and cache are prepared in the deployment environment

## Documentation

- API documentation is available in API_DOCUMENTATION.md
- Phase progress notes are available in PHASE_1_FOUNDATION.md
