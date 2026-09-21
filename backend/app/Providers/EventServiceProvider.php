<?php

namespace App\Providers;

use App\Events\BookingCancelled;
use App\Events\BookingCompleted;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Events\BookingPickedUp;
use App\Events\BookingRejected;
use App\Events\BranchCreated;
use App\Events\BranchDeleted;
use App\Events\BranchUpdated;
use App\Events\LicenseApproved;
use App\Events\LicenseRejected;
use App\Events\LicenseSubmitted;
use App\Events\MaintenanceCompleted;
use App\Events\MaintenanceCreated;
use App\Events\MaintenanceUpdated;
use App\Events\PaymentCreated;
use App\Events\PaymentFailed;
use App\Events\PaymentRefunded;
use App\Events\PaymentSucceeded;
use App\Events\ReviewCreated;
use App\Events\ReviewUpdated;
use App\Events\StaffCreated;
use App\Events\StaffDeleted;
use App\Events\StaffUpdated;
use App\Events\VehicleCreated;
use App\Events\VehicleDeleted;
use App\Events\VehicleStatusChanged;
use App\Events\VehicleTransferCompleted;
use App\Events\VehicleTransferCreated;
use App\Events\VehicleTransferStatusChanged;
use App\Events\VehicleUpdated;
use App\Listeners\ScheduleReviewReminder;
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
use App\Listeners\SendBranchCreatedNotification;
use App\Listeners\SendBranchDeletedNotification;
use App\Listeners\SendBranchUpdatedNotification;
use App\Listeners\SendLicenseApprovedNotification;
use App\Listeners\SendLicenseRejectedNotification;
use App\Listeners\SendLicenseSubmittedNotification;
use App\Listeners\SendMaintenanceCompletedNotification;
use App\Listeners\SendMaintenanceCreatedNotification;
use App\Listeners\SendMaintenanceUpdatedNotification;
use App\Listeners\SendPaymentFailureNotification;
use App\Listeners\SendPaymentInitializedNotification;
use App\Listeners\SendPaymentRefundedNotification;
use App\Listeners\SendPaymentSuccessNotification;
use App\Listeners\SendReviewCreatedNotification;
use App\Listeners\SendReviewUpdatedNotification;
use App\Listeners\SendStaffCreatedNotification;
use App\Listeners\SendStaffDeletedNotification;
use App\Listeners\SendStaffUpdatedNotification;
use App\Listeners\SendVehicleCreatedNotification;
use App\Listeners\SendVehicleDeletedNotification;
use App\Listeners\SendVehicleStatusChangedNotification;
use App\Listeners\SendVehicleTransferCompletedNotification;
use App\Listeners\SendVehicleTransferCreatedNotification;
use App\Listeners\SendVehicleTransferStatusChangedNotification;
use App\Listeners\SendVehicleUpdatedNotification;
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
            ScheduleReviewReminder::class,
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

        // ─── Vehicle Events ──────────────────────────────────────────
        VehicleCreated::class => [
            SendVehicleCreatedNotification::class,
        ],

        VehicleUpdated::class => [
            SendVehicleUpdatedNotification::class,
        ],

        VehicleDeleted::class => [
            SendVehicleDeletedNotification::class,
        ],

        VehicleStatusChanged::class => [
            SendVehicleStatusChangedNotification::class,
        ],

        // ─── Maintenance Events ──────────────────────────────────────
        MaintenanceCreated::class => [
            SendMaintenanceCreatedNotification::class,
        ],

        MaintenanceUpdated::class => [
            SendMaintenanceUpdatedNotification::class,
        ],

        MaintenanceCompleted::class => [
            SendMaintenanceCompletedNotification::class,
        ],

        // ─── Staff Events ────────────────────────────────────────────
        StaffCreated::class => [
            SendStaffCreatedNotification::class,
        ],

        StaffUpdated::class => [
            SendStaffUpdatedNotification::class,
        ],

        StaffDeleted::class => [
            SendStaffDeletedNotification::class,
        ],

        // ─── Branch Events ───────────────────────────────────────────
        BranchCreated::class => [
            SendBranchCreatedNotification::class,
        ],

        BranchUpdated::class => [
            SendBranchUpdatedNotification::class,
        ],

        BranchDeleted::class => [
            SendBranchDeletedNotification::class,
        ],

        // ─── License Events ──────────────────────────────────────────
        LicenseSubmitted::class => [
            SendLicenseSubmittedNotification::class,
        ],

        LicenseApproved::class => [
            SendLicenseApprovedNotification::class,
        ],

        LicenseRejected::class => [
            SendLicenseRejectedNotification::class,
        ],

        // ─── Vehicle Transfer Events ─────────────────────────────────
        VehicleTransferCreated::class => [
            SendVehicleTransferCreatedNotification::class,
        ],

        VehicleTransferStatusChanged::class => [
            SendVehicleTransferStatusChangedNotification::class,
        ],

        VehicleTransferCompleted::class => [
            SendVehicleTransferCompletedNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
