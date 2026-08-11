<?php

namespace App\Services;

use App\Events\PaymentCreated;
use App\Events\PaymentFailed;
use App\Events\PaymentRefunded;
use App\Events\PaymentSucceeded;
use App\Exceptions\PaymentVerificationRetryableException;
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
        private ChapaService $chapaService,
        private AuditLogService $auditLogService
    ) {}

    public function getPaymentsForUser(User $user): LengthAwarePaginator
    {
        return $this->buildPaymentQuery($user)->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Read-only payment history with filters (admin / branch manager / staff).
     */
    public function getPaymentHistory(User $user, array $filters = []): LengthAwarePaginator
    {
        // Payment history includes all records (including archived) — immutable financial audit trail.
        $query = Payment::with(['booking.vehicle', 'booking.user', 'user', 'branch']);

        if ($user->isAdmin()) {
            // company-wide
        } elseif ($user->isBranchManager() || $user->isStaff() || $user->isFleetManager()) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->where('user_id', $user->id);
        }

        if (!empty($filters['branch_id']) && $user->isAdmin()) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'ilike', "%{$search}%")
                    ->orWhere('receipt_number', 'ilike', "%{$search}%")
                    ->orWhere('gateway_reference', 'ilike', "%{$search}%")
                    ->orWhereHas('booking', fn ($b) => $b->where('booking_reference', 'ilike', "%{$search}%"));
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    private function buildPaymentQuery(User $user)
    {
        $query = Payment::with(['booking.vehicle', 'booking.user', 'user', 'branch'])
            ->activeRecords();

        if ($user->isAdmin()) {
            // company-wide
        } elseif ($user->isBranchManager() || $user->isStaff() || $user->isFleetManager()) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    /**
     * @return array{checkout_url: string, tx_ref: string, payment: Payment}
     */
    public function initializePayment(array $data, int $userId): array
    {
        $booking = Booking::findOrFail($data['booking_id']);

        $this->validateBookingOwnership($booking, $userId);
        $this->validateBookingEligibleForPayment($booking);
        $this->cleanUpOrphanedPayments($booking);

        // Reuse a recent pending online payment instead of creating duplicates
        $existingPending = $booking->payments()
            ->where('payment_method', Payment::METHOD_ONLINE_PAYMENT)
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest()
            ->first();

        if ($existingPending) {
            $txRef = $existingPending->transaction_reference;
            $payment = $existingPending;
            Log::info('Reusing existing pending payment for Chapa init', [
                'payment_id' => $payment->id,
                'tx_ref' => $txRef,
                'booking_id' => $booking->id,
            ]);
        } else {
            $this->validateNoDuplicatePaidPayment($booking);

            $txRef = $this->chapaService->generateTransactionRef($booking->id);

            $payment = DB::transaction(function () use ($booking, $userId, $txRef) {
                $payment = Payment::create([
                    'booking_id'            => $booking->id,
                    'user_id'               => $userId,
                    'branch_id'             => $booking->branch_id,
                    'amount'                => $booking->total_price,
                    'currency'              => 'ETB',
                    'payment_method'        => Payment::METHOD_ONLINE_PAYMENT,
                    'gateway'               => Payment::GATEWAY_CHAPA,
                    'transaction_reference' => $txRef,
                    'status'                => Payment::STATUS_PENDING,
                    'verification_status'   => Payment::VERIFICATION_UNVERIFIED,
                ]);

                $booking->update([
                    'payment_status' => Booking::PAYMENT_STATUS_PENDING,
                ]);

                return $payment;
            });

            event(new PaymentCreated($booking, $payment));
        }

        $user = User::find($userId);
        $frontend = rtrim((string) (config('services.chapa.return_url') ?: (env('FRONTEND_URL', 'http://localhost:5173') . '/payments/status')), '/');

        // Always land on payment status page with booking + tx_ref for reliable verification
        $returnUrl = $frontend
            . (str_contains($frontend, '?') ? '&' : '?')
            . http_build_query([
                'booking_id' => $booking->id,
                'tx_ref' => $txRef,
            ]);

        try {
            $checkoutData = $this->chapaService->initializePayment([
                'tx_ref' => $txRef,
                'amount' => (float) $booking->total_price,
                'currency' => 'ETB',
                'email' => $user->email,
                'first_name' => explode(' ', $user->name)[0] ?? '',
                'last_name' => explode(' ', $user->name)[1] ?? '',
                'title' => 'Car Rental',
                'description' => 'Payment for booking ' . $booking->booking_reference,
                'callback_url' => url('/api/payments/callback'),
                'return_url' => $returnUrl,
            ]);

            $payment->update([
                'status' => Payment::STATUS_PROCESSING,
                'gateway' => Payment::GATEWAY_CHAPA,
            ]);

            $this->auditLogService->log(
                $user,
                'chapa_payment_initialized',
                'payment',
                $payment->id,
                ['status' => Payment::STATUS_PENDING],
                ['status' => Payment::STATUS_PROCESSING, 'tx_ref' => $txRef],
                'Redirected customer to Chapa checkout',
                $payment->branch_id
            );
        } catch (\RuntimeException $e) {
            try {
                $this->markAsFailed($payment, $e->getMessage());
            } catch (\Exception $markEx) {
                Log::error('Failed to mark payment as failed after Chapa init error', [
                    'payment_id' => $payment->id,
                    'error' => $markEx->getMessage(),
                ]);
            }

            throw $e;
        }

        Log::info('Payment initialized with Chapa', [
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'tx_ref' => $txRef,
            'amount' => $booking->total_price,
            'return_url' => $returnUrl,
        ]);

        return [
            'checkout_url' => $checkoutData['checkout_url'],
            'tx_ref' => $txRef,
            'payment' => $payment->fresh()->load('booking'),
        ];
    }

    /**
     * Server-side verify with Chapa and sync DB. Idempotent.
     */
    public function verifyPayment(string $txRef, ?User $actor = null, string $source = 'api'): Payment
    {
        $payment = Payment::where('transaction_reference', $txRef)->firstOrFail();

        if ($payment->status === Payment::STATUS_PAID && $payment->isVerified()) {
            Log::info('[Payment] Already paid — idempotent verify', ['tx_ref' => $txRef]);
            return $payment->fresh()->load('booking');
        }

        if ($payment->status === Payment::STATUS_REFUNDED) {
            return $payment->fresh()->load('booking');
        }

        try {
            $verification = $this->chapaService->verifyTransaction($txRef);

            $payment->update([
                'gateway_status' => $verification['status'],
                'gateway_response' => $verification['raw'],
            ]);

            if ($verification['status'] === 'success') {
                $this->assertVerificationMatchesPayment($payment, $verification);
                return $this->markAsPaid(
                    $payment,
                    $verification['reference'],
                    $actor,
                    $source
                );
            }

            if (in_array($verification['status'], ['pending', 'processing'], true)) {
                $payment->update(['status' => Payment::STATUS_PROCESSING]);
                Log::info('[Chapa] Still pending/processing', ['tx_ref' => $txRef, 'status' => $verification['status']]);
                return $payment->fresh()->load('booking');
            }

            $this->markAsFailed($payment, 'Chapa status: ' . $verification['status']);
        } catch (PaymentVerificationRetryableException $e) {
            if ($payment->status !== Payment::STATUS_PAID) {
                $payment->update(['status' => Payment::STATUS_PROCESSING]);
            }
            Log::info('[Chapa] Retryable verification', ['tx_ref' => $txRef, 'message' => $e->getMessage()]);
            throw $e;
        } catch (\InvalidArgumentException $e) {
            Log::error('[Payment] Verification rejected', [
                'tx_ref' => $txRef,
                'error' => $e->getMessage(),
            ]);
            $this->auditLogService->log(
                $actor,
                'payment_verification_rejected',
                'payment',
                $payment->id,
                null,
                ['reason' => $e->getMessage(), 'source' => $source],
                $e->getMessage(),
                $payment->branch_id
            );
            throw $e;
        }

        return $payment->fresh()->load('booking');
    }

    /**
     * Authoritative payment status payload for frontend polling.
     */
    public function getPaymentStatus(Payment $payment, bool $verifyWithChapa = false): array
    {
        if (
            $verifyWithChapa
            && $payment->isGatewayPayment()
            && $payment->transaction_reference
            && in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING], true)
        ) {
            try {
                $payment = $this->verifyPayment($payment->transaction_reference, null, 'status_poll');
            } catch (PaymentVerificationRetryableException) {
                $payment = $payment->fresh();
            } catch (\InvalidArgumentException) {
                $payment = $payment->fresh();
            }
        }

        $payment->refresh();
        $payment->load('booking.vehicle', 'booking.user', 'branch');

        return [
            'payment_id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'status' => $payment->status,
            'verification_status' => $payment->verification_status,
            'payment_method' => $payment->payment_method,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency ?? 'ETB',
            'tx_ref' => $payment->transaction_reference,
            'chapa_reference' => $payment->gateway_reference,
            'gateway_status' => $payment->gateway_status,
            'paid_at' => $payment->paid_at?->toISOString(),
            'verified_at' => $payment->verified_at?->toISOString(),
            'verification_source' => $payment->verification_source,
            'booking_status' => $payment->booking?->status,
            'booking_payment_status' => $payment->booking?->payment_status,
            'failure_reason' => $payment->failure_reason,
            'payment' => $payment,
        ];
    }

    public function verifyPaymentById(Payment $payment, ?User $actor = null, string $source = 'manual'): Payment
    {
        if (!$payment->transaction_reference) {
            throw new \InvalidArgumentException('Payment has no transaction reference to verify.');
        }

        return $this->verifyPayment($payment->transaction_reference, $actor, $source);
    }

    /**
     * Payment status for a booking — verifies with Chapa if still pending/processing.
     */
    public function getBookingPaymentStatus(Booking $booking, bool $verifyWithChapa = true): array
    {
        $payment = $booking->payments()
            ->where('payment_method', Payment::METHOD_ONLINE_PAYMENT)
            ->latest()
            ->first();

        if (!$payment) {
            $payment = $booking->payments()->latest()->first();
        }

        if (
            $verifyWithChapa
            && $payment
            && $payment->transaction_reference
            && in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING], true)
        ) {
            try {
                $payment = $this->verifyPayment($payment->transaction_reference, null, 'status_check');
            } catch (PaymentVerificationRetryableException) {
                $payment = $payment->fresh();
            } catch (\Exception $e) {
                Log::warning('Auto-verify on status check failed', [
                    'booking_id' => $booking->id,
                    'tx_ref' => $payment->transaction_reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $booking->refresh();

        return [
            'booking_id' => $booking->id,
            'booking_status' => $booking->status,
            'booking_payment_status' => $booking->payment_status,
            'payment' => $payment?->fresh()->load('booking'),
            'payment_status' => $payment?->status ?? $booking->payment_status,
            'verification_status' => $payment?->verification_status,
            'tx_ref' => $payment?->transaction_reference,
            'chapa_reference' => $payment?->gateway_reference,
            'amount' => $payment ? (float) $payment->amount : (float) $booking->total_price,
            'currency' => $payment?->currency ?? 'ETB',
            'verified_at' => $payment?->verified_at?->toISOString(),
            'paid_at' => $payment?->paid_at?->toISOString(),
        ];
    }

    public function handleCallback(array $callbackData): void
    {
        $txRef = $callbackData['tx_ref']
            ?? $callbackData['trx_ref']
            ?? null;

        if (!$txRef) {
            Log::warning('Callback received without tx_ref', ['keys' => array_keys($callbackData)]);
            return;
        }

        Log::info('Payment callback received', ['tx_ref' => $txRef]);

        try {
            $this->verifyPayment($txRef, null, 'callback');
        } catch (\Exception $e) {
            Log::error('Callback verification failed', [
                'tx_ref' => $txRef,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handleWebhook(array $payload, string $rawBody, ?string $signature): void
    {
        if (!$this->chapaService->validateWebhookSignature($rawBody, $signature)) {
            Log::warning('Chapa webhook signature validation failed');
            throw new \InvalidArgumentException('Invalid webhook signature.');
        }

        $txRef = $payload['tx_ref']
            ?? $payload['trx_ref']
            ?? data_get($payload, 'data.tx_ref')
            ?? data_get($payload, 'data.trx_ref');

        if (!$txRef) {
            Log::warning('Webhook without tx_ref', ['keys' => array_keys($payload)]);
            return;
        }

        Log::info('Chapa webhook received', [
            'tx_ref' => $txRef,
            'event' => $payload['event'] ?? $payload['type'] ?? null,
        ]);

        // Always re-query Chapa — never trust webhook body alone
        $this->verifyPayment($txRef, null, 'webhook');
    }

    public function processPayment(array $data, int $userId): Payment
    {
        if (($data['payment_method'] ?? '') === Payment::METHOD_CASH) {
            return $this->createCashPendingPayment($data, $userId);
        }

        throw new \InvalidArgumentException('Use payment initialization for online payments.');
    }

    /**
     * Customer selects cash — never auto-mark as PAID.
     */
    public function createCashPendingPayment(array $data, int $userId): Payment
    {
        return DB::transaction(function () use ($data, $userId) {
            $booking = Booking::findOrFail($data['booking_id']);

            $this->validateBookingOwnership($booking, $userId);
            $this->validateBookingEligibleForPayment($booking);
            $this->validateNoDuplicatePaidPayment($booking);
            $this->validatePaymentAmount($data, $booking);

            $existingCash = $booking->payments()
                ->where('payment_method', Payment::METHOD_CASH)
                ->where('status', Payment::STATUS_CASH_PENDING)
                ->first();

            if ($existingCash) {
                return $existingCash->fresh()->load('booking');
            }

            $payment = Payment::create([
                'booking_id'            => $booking->id,
                'user_id'               => $userId,
                'branch_id'             => $booking->branch_id,
                'amount'                => $booking->total_price,
                'currency'              => 'ETB',
                'payment_method'        => Payment::METHOD_CASH,
                'transaction_reference' => $this->generateTransactionRef(),
                'status'                => Payment::STATUS_CASH_PENDING,
                'verification_status'   => Payment::VERIFICATION_UNVERIFIED,
            ]);

            $booking->update([
                'payment_status' => Booking::PAYMENT_STATUS_CASH_PENDING,
            ]);

            event(new PaymentCreated($booking, $payment));

            Log::info('Cash payment pending — awaiting branch confirmation', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
            ]);

            return $payment->fresh()->load('booking');
        });
    }

    /**
     * Staff / branch manager / admin confirms cash received at branch.
     */
    public function confirmCashPayment(Payment $payment, User $actor): Payment
    {
        if ($payment->payment_method !== Payment::METHOD_CASH) {
            throw new \InvalidArgumentException('Only cash payments can be confirmed this way.');
        }

        if ($payment->status !== Payment::STATUS_CASH_PENDING) {
            throw new \InvalidArgumentException('Payment is not awaiting cash confirmation.');
        }

        if (!$actor->isAdmin()) {
            if (!$actor->isBranchManager() && !$actor->isStaff()) {
                throw new \InvalidArgumentException('You are not authorized to confirm cash payments.');
            }
            if ((int) $actor->branch_id !== (int) $payment->branch_id) {
                throw new \InvalidArgumentException('You can only confirm payments for your own branch.');
            }
        }

        $expected = round((float) $payment->amount, 2);
        if ($expected <= 0) {
            throw new \InvalidArgumentException('Invalid payment amount.');
        }

        return DB::transaction(function () use ($payment, $actor) {
            $payment->refresh();

            if ($payment->status === Payment::STATUS_PAID) {
                return $payment->fresh()->load('booking');
            }

            if ($payment->status !== Payment::STATUS_CASH_PENDING) {
                throw new \InvalidArgumentException('Payment is no longer awaiting cash confirmation.');
            }

            $receiptNumber = $this->generateCashReceiptNumber();
            $oldStatus = $payment->status;

            $payment->update([
                'status' => Payment::STATUS_PAID,
                'receipt_number' => $receiptNumber,
                'paid_at' => now(),
                'confirmed_by' => $actor->id,
                'confirmed_at' => now(),
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'verification_source' => 'staff_cash_confirmation',
                'verification_status' => Payment::VERIFICATION_MANUALLY_CONFIRMED,
            ]);

            $payment->booking->update([
                'payment_status' => Booking::PAYMENT_STATUS_PAID,
            ]);

            $this->auditLogService->log(
                $actor,
                'cash_payment_confirmed',
                'payment',
                $payment->id,
                ['status' => $oldStatus],
                [
                    'status' => Payment::STATUS_PAID,
                    'receipt_number' => $receiptNumber,
                    'amount' => (float) $payment->amount,
                ],
                "Cash receipt {$receiptNumber}",
                $payment->branch_id
            );

            event(new PaymentSucceeded($payment->booking, $payment));

            Log::info('Cash payment confirmed', [
                'payment_id' => $payment->id,
                'receipt_number' => $receiptNumber,
                'confirmed_by' => $actor->id,
                'branch_id' => $payment->branch_id,
            ]);

            return $payment->fresh()->load(['booking', 'branch']);
        });
    }

    public function markAsPaid(
        Payment $payment,
        ?string $reference = null,
        ?User $actor = null,
        string $source = 'api'
    ): Payment {
        return DB::transaction(function () use ($payment, $reference, $actor, $source) {
            if ($payment->status === Payment::STATUS_PAID) {
                return $payment->fresh()->load('booking');
            }

            $payment->update([
                'status' => Payment::STATUS_PAID,
                'paid_at' => $payment->paid_at ?? now(),
                'verified_at' => now(),
                'verified_by' => $actor?->id,
                'verification_source' => $source,
                'verification_status' => Payment::VERIFICATION_VERIFIED,
                'gateway_reference' => $reference ?? $payment->gateway_reference,
                'gateway' => $payment->gateway ?? Payment::GATEWAY_CHAPA,
                'gateway_status' => $payment->gateway_status ?? 'success',
                'failure_reason' => null,
            ]);

            $payment->booking->update([
                'payment_status' => Booking::PAYMENT_STATUS_PAID,
            ]);

            if ($actor) {
                $this->auditLogService->log(
                    $actor,
                    'payment_verified',
                    'payment',
                    $payment->id,
                    null,
                    [
                        'status' => Payment::STATUS_PAID,
                        'gateway_reference' => $reference ?? $payment->gateway_reference,
                        'source' => $source,
                    ],
                    "Payment verified via {$source}",
                    $payment->branch_id
                );
            }

            event(new PaymentSucceeded($payment->booking, $payment));

            Log::info('Payment marked as paid', [
                'payment_id' => $payment->id,
                'tx_ref' => $payment->transaction_reference,
                'gateway_reference' => $reference,
                'source' => $source,
                'actor_id' => $actor?->id,
            ]);

            return $payment->fresh()->load('booking');
        });
    }

    public function markAsFailed(Payment $payment, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            if (in_array($payment->status, [Payment::STATUS_FAILED, Payment::STATUS_PAID, Payment::STATUS_REFUNDED], true)) {
                return $payment->fresh();
            }

            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => $reason,
                'verification_status' => Payment::VERIFICATION_UNVERIFIED,
            ]);

            // Only set booking failed if no other paid payment exists
            $hasPaid = $payment->booking->payments()
                ->where('status', Payment::STATUS_PAID)
                ->exists();

            if (!$hasPaid) {
                $payment->booking->update([
                    'payment_status' => Booking::PAYMENT_STATUS_FAILED,
                ]);
            }

            event(new PaymentFailed($payment->booking, $payment));

            Log::info('Payment marked as failed', [
                'payment_id' => $payment->id,
                'tx_ref' => $payment->transaction_reference,
                'reason' => $reason,
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

            return $payment->fresh();
        });
    }

    private function assertVerificationMatchesPayment(Payment $payment, array $verification): void
    {
        if (!empty($verification['tx_ref']) && $verification['tx_ref'] !== $payment->transaction_reference) {
            throw new \InvalidArgumentException('Chapa tx_ref does not match the stored payment reference.');
        }

        $expected = round((float) $payment->amount, 2);
        $actual = round((float) $verification['amount'], 2);

        if ($actual > 0 && abs($expected - $actual) > 0.01) {
            Log::error('Chapa amount mismatch', [
                'payment_id' => $payment->id,
                'expected' => $expected,
                'actual' => $actual,
            ]);
            throw new \InvalidArgumentException('Payment amount does not match the Chapa transaction amount.');
        }

        $expectedCurrency = strtoupper((string) ($payment->currency ?: 'ETB'));
        $actualCurrency = strtoupper((string) ($verification['currency'] ?? 'ETB'));

        if ($actualCurrency && $expectedCurrency !== $actualCurrency) {
            throw new \InvalidArgumentException('Payment currency does not match the Chapa transaction currency.');
        }
    }

    private function validateBookingOwnership(Booking $booking, int $userId): void
    {
        if ($booking->user_id !== $userId) {
            throw new \InvalidArgumentException('Unauthorized payment for this booking.');
        }
    }

    private function validateBookingEligibleForPayment(Booking $booking): void
    {
        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            throw new \InvalidArgumentException('Booking must be confirmed by the branch before payment.');
        }
    }

    private function validateNoDuplicatePaidPayment(Booking $booking): void
    {
        if ($booking->payments()->where('status', Payment::STATUS_PAID)->exists()) {
            throw new \InvalidArgumentException('This booking has already been paid.');
        }
    }

    private function cleanUpOrphanedPayments(Booking $booking): void
    {
        // Mark expired attempts as failed — never physically delete payment rows.
        $booking->payments()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING, Payment::STATUS_FAILED])
            ->where('created_at', '<', now()->subHours(2))
            ->where('is_archived', false)
            ->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => 'Expired pending payment',
            ]);
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

    private function generateCashReceiptNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "CASH-{$year}-";

        $last = Payment::where('receipt_number', 'like', $prefix . '%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $sequence = 1;
        if ($last && preg_match('/CASH-\d{4}-(\d+)/', $last, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
