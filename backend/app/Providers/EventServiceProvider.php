<?php

namespace App\Providers;

use App\Events\BookingCancelled;
use App\Events\BookingCompleted;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Events\BookingPickedUp;
use App\Events\BookingRejected;
use App\Events\PaymentCreated;
use App\Events\PaymentFailed;
use App\Events\PaymentRefunded;
use App\Events\PaymentSucceeded;
use App\Events\ReviewCreated;
use App\Events\ReviewUpdated;
use App\Listeners\SendAdminBookingCancelledNotification;
use App\Listeners\SendAdminBookingCompletedNotification;
use App\Listeners\SendAdminBookingConfirmedNotification;
use App\Listeners\SendAdminBookingPickedUpNotification;
use App\Listeners\SendAdminBookingRejectedNotification;
use App\Listeners\SendAdminNewBookingNotification;
use App\Listeners\SendAdminNewReviewNotification;
use App\Listeners\SendAdminPaymentCompletedNotification;
use App\Listeners\SendBookingCancelledNotification;
use App\Listeners\SendBookingCompletedNotification;
use App\Listeners\SendBookingConfirmedNotification;
use App\Listeners\SendBookingCreatedNotification;
use App\Listeners\SendBookingPickedUpNotification;
use App\Listeners\SendBookingRejectedNotification;
use App\Listeners\SendPaymentFailureNotification;
use App\Listeners\SendPaymentInitializedNotification;
use App\Listeners\SendPaymentRefundedNotification;
use App\Listeners\SendPaymentSuccessNotification;
use App\Listeners\SendReviewCreatedNotification;
use App\Listeners\SendReviewUpdatedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // ─── Booking Events ──────────────────────────────────────────
        BookingCreated::class => [
            SendBookingCreatedNotification::class,
            SendAdminNewBookingNotification::class,
        ],

        BookingConfirmed::class => [
            SendBookingConfirmedNotification::class,
            SendAdminBookingConfirmedNotification::class,
        ],

        BookingRejected::class => [
            SendBookingRejectedNotification::class,
            SendAdminBookingRejectedNotification::class,
        ],

        BookingCancelled::class => [
            SendBookingCancelledNotification::class,
            SendAdminBookingCancelledNotification::class,
        ],

        BookingPickedUp::class => [
            SendBookingPickedUpNotification::class,
            SendAdminBookingPickedUpNotification::class,
        ],

        BookingCompleted::class => [
            SendBookingCompletedNotification::class,
            SendAdminBookingCompletedNotification::class,
        ],

        // ─── Payment Events ─────────────────────────────────────────
        PaymentCreated::class => [
            SendPaymentInitializedNotification::class,
        ],

        PaymentSucceeded::class => [
            SendPaymentSuccessNotification::class,
            SendAdminPaymentCompletedNotification::class,
        ],

        PaymentFailed::class => [
            SendPaymentFailureNotification::class,
        ],

        PaymentRefunded::class => [
            SendPaymentRefundedNotification::class,
        ],

        // ─── Review Events ──────────────────────────────────────────
        ReviewCreated::class => [
            SendReviewCreatedNotification::class,
            SendAdminNewReviewNotification::class,
        ],

        ReviewUpdated::class => [
            SendReviewUpdatedNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
