<?php

namespace App\Services;

use App\Events\PaymentCreated;
use App\Events\PaymentFailed;
use App\Events\PaymentRefunded;
use App\Events\PaymentSucceeded;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function getPaymentsForUser(User $user): LengthAwarePaginator
    {
        $query = Payment::with(['booking']);

        if (!in_array($user->role, ['admin', 'staff', 'fleet_manager'])) {
            $query->where('user_id', $user->id);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function processPayment(array $data, int $userId): Payment
    {
        return DB::transaction(function () use ($data, $userId) {
            $booking = Booking::findOrFail($data['booking_id']);

            $this->validateBookingOwnership($booking, $userId);
            $this->validateBookingEligibleForPayment($booking);
            $this->validateNoDuplicatePayment($booking);
            $this->validatePaymentAmount($data, $booking);

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'amount' => $booking->total_price,
                'payment_method' => $data['payment_method'],
                'transaction_reference' => $data['transaction_reference'] ?? $this->generateTransactionRef(),
                'status' => Payment::STATUS_PAID,
                'paid_at' => now(),
            ]);

            $booking->update([
                'payment_status' => Booking::PAYMENT_STATUS_PAID,
            ]);

            event(new PaymentCreated($booking, $payment));
            event(new PaymentSucceeded($booking, $payment));

            return $payment->fresh()->load('booking');
        });
    }

    public function markAsFailed(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            if ($payment->status === Payment::STATUS_FAILED) {
                throw new \InvalidArgumentException('Payment is already marked as failed.');
            }

            if ($payment->status === Payment::STATUS_REFUNDED) {
                throw new \InvalidArgumentException('Refunded payments cannot be marked as failed.');
            }

            $payment->update(['status' => Payment::STATUS_FAILED]);

            $payment->booking->update([
                'payment_status' => Booking::PAYMENT_STATUS_FAILED,
            ]);

            event(new PaymentFailed($payment->booking, $payment));

            return $payment->fresh();
        });
    }

    public function refundPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            if ($payment->status !== Payment::STATUS_PAID) {
                throw new \InvalidArgumentException('Only paid payments can be refunded.');
            }

            $payment->update(['status' => Payment::STATUS_REFUNDED]);

            $payment->booking->update([
                'payment_status' => Booking::PAYMENT_STATUS_REFUNDED,
            ]);

            event(new PaymentRefunded($payment->booking, $payment));

            return $payment->fresh();
        });
    }

    private function validateBookingOwnership(Booking $booking, int $userId): void
    {
        if ($booking->user_id !== $userId) {
            throw new \InvalidArgumentException('Unauthorized payment for this booking.');
        }
    }

    private function validateBookingEligibleForPayment(Booking $booking): void
    {
        if (!in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])) {
            throw new \InvalidArgumentException('Booking is not eligible for payment.');
        }
    }

    private function validateNoDuplicatePayment(Booking $booking): void
    {
        $existingPaidPayment = $booking->payments()
            ->where('status', Payment::STATUS_PAID)
            ->exists();

        if ($existingPaidPayment) {
            throw new \InvalidArgumentException('This booking has already been paid.');
        }

        $existingPendingPayment = $booking->payments()
            ->where('status', Payment::STATUS_PENDING)
            ->exists();

        if ($existingPendingPayment) {
            throw new \InvalidArgumentException('A pending payment already exists for this booking.');
        }
    }

    private function validatePaymentAmount(array $data, Booking $booking): void
    {
        if (isset($data['amount']) && (float) $data['amount'] !== (float) $booking->total_price) {
            throw new \InvalidArgumentException('Payment amount must match the booking total.');
        }
    }

    private function generateTransactionRef(): string
    {
        return 'TXN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
    }
}
