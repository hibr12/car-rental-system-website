<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CustomNotification;
use App\Models\Invoice;
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
                'status' => 'confirmed',
            ]);

            $this->generateInvoice($booking, $payment);

            $this->createNotification(
                $booking->user_id,
                'payment_success',
                'Payment Successful',
                "Payment of $" . number_format($data['amount'], 2) . " for reservation #{$booking->booking_reference} was successful.",
                Booking::class,
                $booking->id
            );

            $this->notifyBranchStaff($booking->branch_id, 'payment_completed', 'Payment Received', "Payment for reservation #{$booking->booking_reference} has been completed.", Booking::class, $booking->id);

            return $payment->fresh()->load('booking');
        });
    }

    public function markAsFailed(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'failed']);

            $payment->booking->update(['payment_status' => 'failed']);

            $this->createNotification(
                $payment->user_id,
                'payment_failed',
                'Payment Failed',
                "Payment for reservation #{$payment->booking->booking_reference} has failed.",
                Booking::class,
                $payment->booking_id
            );

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

            $this->createNotification(
                $payment->user_id,
                'payment_refunded',
                'Payment Refunded',
                "Payment for reservation #{$payment->booking->booking_reference} has been refunded.",
                Booking::class,
                $payment->booking_id
            );

            return $payment->fresh();
        });
    }

    private function generateInvoice(Booking $booking, Payment $payment): void
    {
        Invoice::create([
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'payment_id' => $payment->id,
            'subtotal' => $booking->subtotal,
            'additional_charges' => $booking->additional_charges,
            'discount' => $booking->discount,
            'tax_amount' => 0,
            'total_amount' => $booking->total_price,
            'status' => 'paid',
            'issued_at' => now(),
            'paid_at' => $payment->paid_at,
        ]);
    }

    private function generateTransactionRef(): string
    {
        return 'TXN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
    }

    private function createNotification(int $userId, string $type, string $title, string $message, ?string $relatedType = null, ?int $relatedId = null): void
    {
        CustomNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'notifiable_type' => \App\Models\User::class,
            'notifiable_id' => $userId,
            'data' => json_encode([]),
        ]);
    }

    private function notifyBranchStaff(?int $branchId, string $type, string $title, string $message, ?string $relatedType = null, ?int $relatedId = null): void
    {
        if (!$branchId) {
            return;
        }

        $staffUsers = \App\Models\User::where('branch_id', $branchId)
            ->whereIn('role', ['staff', 'branch_manager'])
            ->get();

        foreach ($staffUsers as $staff) {
            $this->createNotification($staff->id, $type, $title, $message, $relatedType, $relatedId);
        }
    }
}
