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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private ChapaService $chapaService
    ) {}

    public function getPaymentsForUser(User $user): LengthAwarePaginator
    {
        $query = Payment::with(['booking']);

        if (!in_array($user->role, ['admin', 'staff', 'fleet_manager'])) {
            $query->where('user_id', $user->id);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Initialize a payment for a booking via Chapa.
     *
     * @return array{checkout_url: string, tx_ref: string, payment: Payment}
     */
    public function initializePayment(array $data, int $userId): array
    {
        $booking = Booking::findOrFail($data['booking_id']);

        $this->validateBookingOwnership($booking, $userId);
        $this->validateBookingEligibleForPayment($booking);
        $this->validateNoDuplicatePayment($booking);

        $txRef = $this->chapaService->generateTransactionRef();

        $payment = DB::transaction(function () use ($booking, $userId, $txRef) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'amount' => $booking->total_price,
                'payment_method' => Payment::METHOD_ONLINE_PAYMENT,
                'transaction_reference' => $txRef,
                'status' => Payment::STATUS_PENDING,
            ]);

            $booking->update([
                'payment_status' => Booking::PAYMENT_STATUS_PENDING,
            ]);

            return $payment;
        });

        event(new PaymentCreated($booking, $payment));

        $user = User::find($userId);

        try {
            $checkoutData = $this->chapaService->initializePayment([
                'tx_ref' => $txRef,
                'amount' => $booking->total_price,
                'currency' => 'ETB',
                'email' => $user->email,
                'first_name' => explode(' ', $user->name)[0] ?? '',
                'last_name' => explode(' ', $user->name)[1] ?? '',
                'title' => 'Car Rental Payment',
                'description' => 'Payment for booking ' . $booking->booking_reference,
                'callback_url' => route('payments.callback'),
                'return_url' => config('services.chapa.return_url', env('FRONTEND_URL', 'http://localhost:5173') . '/payments/status'),
            ]);
        } catch (\RuntimeException $e) {
            $this->markAsFailed($payment);

            throw $e;
        }

        Log::info('Payment initialized', [
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'tx_ref' => $txRef,
            'amount' => $booking->total_price,
        ]);

        return [
            'checkout_url' => $checkoutData['checkout_url'],
            'tx_ref' => $txRef,
            'payment' => $payment->fresh()->load('booking'),
        ];
    }

    /**
     * Verify a payment transaction with Chapa and update records.
     */
    public function verifyPayment(string $txRef): Payment
    {
        $payment = Payment::where('transaction_reference', $txRef)->firstOrFail();

        if ($payment->status === Payment::STATUS_PAID) {
            Log::info('Payment already verified', ['tx_ref' => $txRef]);
            return $payment->fresh()->load('booking');
        }

        $verification = $this->chapaService->verifyTransaction($txRef);

        if ($verification['status'] === 'success') {
            $this->markAsPaid($payment, $verification['reference']);
        } else {
            $this->markAsFailed($payment);
        }

        return $payment->fresh()->load('booking');
    }

    /**
     * Handle a callback/webhook from Chapa.
     */
    public function handleCallback(array $callbackData): void
    {
        $txRef = $callbackData['tx_ref'] ?? null;

        if (!$txRef) {
            Log::warning('Callback received without tx_ref', ['data' => $callbackData]);
            return;
        }

        $payment = Payment::where('transaction_reference', $txRef)->first();

        if (!$payment) {
            Log::warning('Callback for unknown transaction', ['tx_ref' => $txRef]);
            return;
        }

        if ($payment->status === Payment::STATUS_PAID) {
            Log::info('Duplicate callback ignored', ['tx_ref' => $txRef]);
            return;
        }

        try {
            $this->verifyPayment($txRef);
        } catch (\Exception $e) {
            Log::error('Callback verification failed', [
                'tx_ref' => $txRef,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process a direct payment (non-Chapa).
     */
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

    /**
     * Mark a payment as paid.
     */
    public function markAsPaid(Payment $payment, ?string $reference = null): Payment
    {
        return DB::transaction(function () use ($payment, $reference) {
            $payment->update([
                'status' => Payment::STATUS_PAID,
                'paid_at' => now(),
            ]);

            $payment->booking->update([
                'payment_status' => Booking::PAYMENT_STATUS_PAID,
            ]);

            event(new PaymentSucceeded($payment->booking, $payment));

            Log::info('Payment marked as paid', [
                'payment_id' => $payment->id,
                'tx_ref' => $payment->transaction_reference,
            ]);

            return $payment->fresh();
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

            Log::info('Payment marked as failed', [
                'payment_id' => $payment->id,
                'tx_ref' => $payment->transaction_reference,
            ]);

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

            Log::info('Payment refunded', [
                'payment_id' => $payment->id,
                'tx_ref' => $payment->transaction_reference,
            ]);

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
