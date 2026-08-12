<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Approval Rules
    |--------------------------------------------------------------------------
    |
    | Normal bookings require branch approval only. Admin approval is required
    | when any of these thresholds are met (or force_admin_approval is set).
    |
    */

    'admin_approval' => [
        'high_value_threshold' => (float) env('BOOKING_ADMIN_HIGH_VALUE', 50000),
        'long_rental_days' => (int) env('BOOKING_ADMIN_LONG_RENTAL_DAYS', 14),
        'discount_percent_threshold' => (float) env('BOOKING_ADMIN_DISCOUNT_PERCENT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pickup readiness
    |--------------------------------------------------------------------------
    |
    | Confirmed bookings become ready_for_pickup when the pickup date is within
    | this many hours (or already past). Set 0 to mark ready immediately after
    | confirmation.
    |
    */

    'ready_for_pickup_hours_before' => (int) env('BOOKING_READY_PICKUP_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Payment deadline after branch approval
    |--------------------------------------------------------------------------
    |
    | Minutes the customer has to complete payment after the booking is
    | approved and becomes payment_required. Set 0 to disable expiration.
    |
    */

    'payment_deadline_minutes' => (int) env('BOOKING_PAYMENT_DEADLINE_MINUTES', 30),

];
