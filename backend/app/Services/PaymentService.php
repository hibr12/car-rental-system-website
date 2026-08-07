<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentSuccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function processPayment(array $data, int $userId): Payment
    {
        return DB::transaction(function () use ($data, $userId) {
            $booking = Booking::findOrFail($data['booking_id']);

            if ($booking->user_id !== $userId) {
                throw new \InvalidArgumentException('Unauthorized payment for this booking.');
            }

            if ($booking->payment_status === 'paid') {
                throw new \InvalidArgumentException('Booking is already paid.');
            }

            if ((float) $data['amount'] !== (float) $booking->total_price) {
                throw new \InvalidArgumentException('Payment amount must match the booking total.');
            }

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'transaction_reference' => $data['transaction_reference'] ?? $this->generateTransactionRef(),
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $booking->update([
                'payment_status' => 'paid',
            ]);

            $booking->user->notify(new PaymentSuccess($booking, $payment));

            return $payment->fresh()->load('booking');
        });
    }

    public function markAsFailed(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'failed']);

            $payment->booking->update(['payment_status' => 'failed']);

            $payment->booking->user->notify(new PaymentFailed($payment->booking, $payment));

            return $payment->fresh();
        });
    }

    public function refundPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            if ($payment->status !== 'paid') {
                throw new \InvalidArgumentException('Only paid payments can be refunded.');
            }

            $payment->update(['status' => 'refunded']);

            $payment->booking->update(['payment_status' => 'refunded']);

            return $payment->fresh();
        });
    }

    private function generateTransactionRef(): string
    {
        return 'TXN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
    }
}