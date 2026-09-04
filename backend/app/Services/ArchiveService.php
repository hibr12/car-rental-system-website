<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ArchiveService
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    /** Statuses eligible for archival (closed operational records). */
    private const ARCHIVABLE_BOOKING_STATUSES = [
        Booking::STATUS_COMPLETED,
        Booking::STATUS_CANCELLED,
        Booking::STATUS_REJECTED,
        Booking::STATUS_EXPIRED,
    ];

    /** Payment statuses that must never be archived (financial history). */
    private const NON_ARCHIVABLE_PAYMENT_STATUSES = [
        Payment::STATUS_PAID,
        Payment::STATUS_REFUNDED,
        Payment::STATUS_CASH_PENDING,
        Payment::STATUS_PROCESSING,
    ];

    public function archiveBooking(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        if (!$actor->isAdmin()) {
            throw new \InvalidArgumentException('Only administrators can archive bookings.');
        }

        if ($booking->is_archived) {
            throw new \InvalidArgumentException('Booking is already archived.');
        }

        if (!in_array($booking->status, self::ARCHIVABLE_BOOKING_STATUSES, true)) {
            throw new \InvalidArgumentException(
                'Only completed, cancelled, rejected, or expired bookings can be archived.'
            );
        }

        if (in_array($booking->status, [Booking::STATUS_ACTIVE, Booking::STATUS_CONFIRMED], true)) {
            throw new \InvalidArgumentException('Active or confirmed bookings cannot be archived.');
        }

        return DB::transaction(function () use ($booking, $actor, $reason) {
            $booking->update([
                'is_archived' => true,
                'archived_at' => now(),
                'archived_by' => $actor->id,
                'archive_reason' => $reason,
            ]);

            $this->auditLogService->log(
                $actor,
                'booking_archived',
                'booking',
                $booking->id,
                ['is_archived' => false],
                ['is_archived' => true, 'archive_reason' => $reason],
                $reason,
                $booking->branch_id
            );

            return $booking->fresh()->load(['vehicle', 'user', 'branch']);
        });
    }

    public function archivePayment(Payment $payment, User $actor, ?string $reason = null): Payment
    {
        if (!$actor->isAdmin()) {
            throw new \InvalidArgumentException('Only administrators can archive payments.');
        }

        if ($payment->is_archived) {
            throw new \InvalidArgumentException('Payment is already archived.');
        }

        if (in_array($payment->status, self::NON_ARCHIVABLE_PAYMENT_STATUSES, true)) {
            throw new \InvalidArgumentException(
                'Successful, refunded, or in-progress payments cannot be archived. Financial records must be preserved.'
            );
        }

        return DB::transaction(function () use ($payment, $actor, $reason) {
            $payment->update([
                'is_archived' => true,
                'archived_at' => now(),
                'archived_by' => $actor->id,
                'archive_reason' => $reason,
            ]);

            $this->auditLogService->log(
                $actor,
                'payment_archived',
                'payment',
                $payment->id,
                ['is_archived' => false],
                ['is_archived' => true, 'archive_reason' => $reason, 'status' => $payment->status],
                $reason,
                $payment->branch_id
            );

            return $payment->fresh()->load(['booking.vehicle', 'booking.user', 'user', 'branch']);
        });
    }

    public function getArchivedBookings(User $user, array $filters = []): LengthAwarePaginator
    {
        if (!$user->isAdmin()) {
            throw new \InvalidArgumentException('Only administrators can view archived bookings.');
        }

        $query = Booking::with(['vehicle', 'user', 'branch'])
            ->where('is_archived', true);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('booking_reference', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%"));
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->orderByDesc('archived_at')->paginate($perPage);
    }

    public function getArchivedPayments(User $user, array $filters = []): LengthAwarePaginator
    {
        if (!$user->isAdmin()) {
            throw new \InvalidArgumentException('Only administrators can view archived payments.');
        }

        $query = Payment::with(['booking.vehicle', 'booking.user', 'user', 'branch'])
            ->where('is_archived', true);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'ilike', "%{$search}%")
                    ->orWhere('receipt_number', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->orderByDesc('archived_at')->paginate($perPage);
    }
}
