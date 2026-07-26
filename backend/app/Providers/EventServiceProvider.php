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
use App\Listeners\SendBookingCancelledNotification;
use App\Listeners\SendBookingCompletedNotification;
use App\Listeners\SendBookingConfirmedNotification;
use App\Listeners\SendBookingCreatedNotification;
use App\Listeners\SendBookingPickedUpNotification;
use App\Listeners\SendBookingRejectedNotification;
use App\Listeners\SendPaymentFailureNotification;
use App\Listeners\SendPaymentSuccessNotification;
use App\Listeners\SendReviewCreatedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingCreated::class => [
            SendBookingCreatedNotification::class,
        ],

        BookingConfirmed::class => [
            SendBookingConfirmedNotification::class,
        ],

        BookingRejected::class => [
            SendBookingRejectedNotification::class,
        ],

        BookingCancelled::class => [
            SendBookingCancelledNotification::class,
        ],

        BookingPickedUp::class => [
            SendBookingPickedUpNotification::class,
        ],

        BookingCompleted::class => [
            SendBookingCompletedNotification::class,
        ],

        PaymentSucceeded::class => [
            SendPaymentSuccessNotification::class,
        ],

        PaymentFailed::class => [
            SendPaymentFailureNotification::class,
        ],

        PaymentCreated::class => [],

        PaymentRefunded::class => [],

        ReviewCreated::class => [
            SendReviewCreatedNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
