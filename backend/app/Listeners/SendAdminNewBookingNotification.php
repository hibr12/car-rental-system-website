<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Notifications\AdminNewBooking;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdminNewBookingNotification implements ShouldQueue
{
    public function __construct(
        private NotificationRecipientService $notificationRecipients
    ) {}

    public function handle(BookingCreated $event): void
    {
        try {
            $booking = $event->booking->loadMissing(['user', 'vehicle', 'branch']);
            $recipients = $this->notificationRecipients
                ->adminsAndBranchManagers((int) $booking->branch_id);

            foreach ($recipients as $recipient) {
                $recipient->notify(new AdminNewBooking($booking));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin new booking notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
