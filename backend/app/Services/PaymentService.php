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
        private AuditLogService $auditLogService,
        private BookingWorkflowService $bookingWorkflow
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
        $query = Payment::with(['booking.vehicle', 'booking.user', 'user', 'branch', 'confirmer']);

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

        if (!empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
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
        $query = Payment::with(['booking.vehicle', 'booking.user', 'user', 'branch', 'confirmer'])
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

            $txRef = $this->chapaService->generateTransactionRef($booking->id, $booking->booking_reference);

            $payment = DB::transaction(function () use ($booking, $userId, $txRef) {
                $expected = number_format((float) $booking->total_price, 2, '.', '');
                $payment = Payment::create([
                    'booking_id'            => $booking->id,
                    'attempt_number'        => $this->nextAttemptNumber($booking),
                    'user_id'               => $userId,
                    'branch_id'             => $booking->branch_id,
                    'amount'                => $expected,
                    'expected_amount'       => $expected,
                    'currency'              => 'ETB',
                    'payment_method'        => Payment::METHOD_ONLINE_PAYMENT,
                    'gateway'               => Payment::GATEWAY_CHAPA,
                    'transaction_reference' => $txRef,
                    'status'                => Payment::STATUS_PENDING,
                    'verification_status'   => Payment::VERIFICATION_UNVERIFIED,
                    'idempotency_key'       => 'chapa:' . $booking->id . ':' . $txRef,
                ]);

                $booking->update([
                    'payment_status' => Booking::PAYMENT_STATUS_PENDING,
                    'status' => Booking::STATUS_PAYMENT_PROCESSING,
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
     * Gateway SUCCESS alone never settles — amount/currency/reference must match.
     */
    public function verifyPayment(string $txRef, ?User $actor = null, string $source = 'api'): Payment
    {
        $payment = Payment::where('transaction_reference', $txRef)->firstOrFail();

        if ($payment->status === Payment::STATUS_PAID && $payment->isVerified()) {
            Log::info('[Payment] Already paid — idempotent verify', ['tx_ref' => $txRef]);
            return $payment->fresh()->load('booking');
        }

        if (in_array($payment->status, [
            Payment::STATUS_REFUNDED,
            Payment::STATUS_PARTIALLY_REFUNDED,
            Payment::STATUS_REFUND_PENDING,
        ], true)) {
            return $payment->fresh()->load('booking');
        }

        $payment->update(['verification_status' => Payment::VERIFICATION_VERIFYING]);

        if ($actor) {
            $this->auditLogService->log(
                $actor,
                'payment_verification_requested',
                'payment',
                $payment->id,
                null,
                ['tx_ref' => $txRef, 'source' => $source],
                'Chapa verification requested',
                $payment->branch_id
            );
        }

        try {
            $verification = $this->chapaService->verifyTransaction($txRef);

            $payment->update([
                'gateway_status' => $verification['status'],
                'gateway_response' => $this->sanitizeGatewayResponse($verification['raw'] ?? []),
                'paid_amount' => isset($verification['amount'])
                    ? number_format((float) $verification['amount'], 2, '.', '')
                    : $payment->paid_amount,
            ]);

            if ($verification['status'] === 'success') {
                $match = $this->evaluateVerificationMatch($payment, $verification);

                if ($match['ok'] !== true) {
                    return $this->markAsInvalid(
                        $payment,
                        $match['verification_status'],
                        $match['reason'],
                        $match['mismatch_reason'] ?? null,
                        $actor,
                        $verification
                    );
                }

                return $this->markAsPaid(
                    $payment,
                    $verification['reference'],
                    $actor,
                    $source === 'manual_verify' ? 'CHAPA_API' : $source,
                    (float) $verification['amount']
                );
            }

            if (in_array($verification['status'], ['pending', 'processing'], true)) {
                $payment->update([
                    'status' => Payment::STATUS_PROCESSING,
                    'verification_status' => Payment::VERIFICATION_GATEWAY_PENDING,
                ]);
                Log::info('[Chapa] Still pending/processing', ['tx_ref' => $txRef, 'status' => $verification['status']]);
                return $payment->fresh()->load('booking');
            }

            return $this->markAsFailed(
                $payment,
                'Chapa status: ' . $verification['status'],
                Payment::VERIFICATION_GATEWAY_FAILED
            );
        } catch (PaymentVerificationRetryableException $e) {
            if (!in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_INVALID], true)) {
                $payment->update([
                    'status' => Payment::STATUS_PROCESSING,
                    'verification_status' => Payment::VERIFICATION_ERROR,
                    'failure_reason' => $e->getMessage(),
                ]);
            }
            Log::info('[Chapa] Retryable verification', ['tx_ref' => $txRef, 'message' => $e->getMessage()]);
            throw $e;
        } catch (\InvalidArgumentException $e) {
            Log::error('[Payment] Verification rejected', [
                'tx_ref' => $txRef,
                'error' => $e->getMessage(),
            ]);
            if ($actor) {
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
            }
            throw $e;
        }
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
            'amount' => (float) ($payment->expected_amount ?? $payment->amount),
            'expected_amount' => (float) ($payment->expected_amount ?? $payment->amount),
            'paid_amount' => $payment->paid_amount !== null ? (float) $payment->paid_amount : null,
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
            'mismatch_reason' => $payment->mismatch_reason,
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
                'attempt_number'        => $this->nextAttemptNumber($booking),
                'user_id'               => $userId,
                'branch_id'             => $booking->branch_id,
                'amount'                => number_format((float) $booking->total_price, 2, '.', ''),
                'expected_amount'       => number_format((float) $booking->total_price, 2, '.', ''),
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
     * Requires amount_received matching expected amount exactly.
     */
    public function confirmCashPayment(Payment $payment, User $actor, ?float $amountReceived = null): Payment
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
                throw new \InvalidArgumentException('You are not authorized to manage this branch payment.');
            }
        }

        if ((int) $payment->branch_id !== (int) $payment->booking->branch_id) {
            throw new \InvalidArgumentException('Payment branch does not match booking branch.');
        }

        $expected = round((float) ($payment->expected_amount ?? $payment->amount), 2);
        if ($expected <= 0) {
            throw new \InvalidArgumentException('Invalid payment amount.');
        }

        $received = $amountReceived !== null
            ? round($amountReceived, 2)
            : $expected;

        if (abs($expected - $received) > 0.01) {
            $mismatch = $received < $expected
                ? Payment::MISMATCH_UNDERPAYMENT
                : Payment::MISMATCH_OVERPAYMENT;

            return $this->markAsInvalid(
                $payment,
                Payment::VERIFICATION_AMOUNT_MISMATCH,
                "Cash amount mismatch. Expected {$expected}, received {$received}.",
                $mismatch,
                $actor,
                ['expected' => $expected, 'received' => $received]
            );
        }

        // Prevent double settlement
        $this->assertNoOtherSettledPayment($payment);

        return DB::transaction(function () use ($payment, $actor, $expected, $received) {
            $payment->refresh();

            if ($payment->status === Payment::STATUS_PAID && $payment->isVerified()) {
                return $payment->fresh()->load('booking');
            }

            if ($payment->status !== Payment::STATUS_CASH_PENDING) {
                throw new \InvalidArgumentException('Payment is no longer awaiting cash confirmation.');
            }

            $receiptNumber = $this->generateCashReceiptNumber();
            $oldStatus = $payment->status;

            $payment->update([
                'status' => Payment::STATUS_PAID,
                'expected_amount' => $expected,
                'paid_amount' => $received,
                'amount_received' => $received,
                'receipt_number' => $receiptNumber,
                'paid_at' => now(),
                'confirmed_by' => $actor->id,
                'confirmed_at' => now(),
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'verification_source' => 'staff_cash_confirmation',
                'verification_status' => Payment::VERIFICATION_MANUALLY_CONFIRMED,
                'failure_reason' => null,
                'mismatch_reason' => null,
            ]);

            $payment->booking->update([
                'payment_status' => Booking::PAYMENT_STATUS_PAID,
            ]);

            $this->auditLogService->log(
                $actor,
                'payment_manually_confirmed',
                'payment',
                $payment->id,
                ['status' => $oldStatus],
                [
                    'status' => Payment::STATUS_PAID,
                    'receipt_number' => $receiptNumber,
                    'expected_amount' => $expected,
                    'amount_received' => $received,
                ],
                "Cash receipt {$receiptNumber}",
                $payment->branch_id
            );

            event(new PaymentSucceeded($payment->booking, $payment));

            try {
                $this->bookingWorkflow->advanceAfterPaymentVerified($payment->booking->fresh(), $actor);
            } catch (\InvalidArgumentException $e) {
                Log::warning('Cash paid but booking not advanced', [
                    'booking_id' => $payment->booking_id,
                    'reason' => $e->getMessage(),
                ]);
            }

            return $payment->fresh()->load(['booking', 'branch']);
        });
    }

    public function markAsPaid(
        Payment $payment,
        ?string $reference = null,
        ?User $actor = null,
        string $source = 'api',
        ?float $paidAmount = null
    ): Payment {
        return DB::transaction(function () use ($payment, $reference, $actor, $source, $paidAmount) {
            $payment->refresh();

            if ($payment->status === Payment::STATUS_PAID && $payment->isVerified()) {
                return $payment->fresh()->load('booking');
            }

            $this->assertNoOtherSettledPayment($payment);

            $expected = (float) ($payment->expected_amount ?? $payment->amount);
            $received = $paidAmount !== null ? $paidAmount : $expected;

            $payment->update([
                'status' => Payment::STATUS_PAID,
                'expected_amount' => $expected,
                'paid_amount' => number_format($received, 2, '.', ''),
                'paid_at' => $payment->paid_at ?? now(),
                'verified_at' => now(),
                'verified_by' => $actor?->id,
                'verification_source' => $source,
                'verification_status' => Payment::VERIFICATION_VERIFIED,
                'gateway_reference' => $reference ?? $payment->gateway_reference,
                'gateway' => $payment->gateway ?? Payment::GATEWAY_CHAPA,
                'gateway_status' => $payment->gateway_status ?? 'success',
                'failure_reason' => null,
                'mismatch_reason' => null,
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
                        'expected_amount' => $expected,
                        'paid_amount' => $received,
                        'gateway_reference' => $reference ?? $payment->gateway_reference,
                        'source' => $source,
                    ],
                    "Payment verified via {$source}",
                    $payment->branch_id
                );
            }

            event(new PaymentSucceeded($payment->booking, $payment));

            try {
                $this->bookingWorkflow->advanceAfterPaymentVerified($payment->booking->fresh(), $actor);
            } catch (\InvalidArgumentException $e) {
                Log::warning('Payment paid but booking not advanced', [
                    'booking_id' => $payment->booking_id,
                    'reason' => $e->getMessage(),
                ]);
            }

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

    public function markAsInvalid(
        Payment $payment,
        string $verificationStatus,
        string $reason,
        ?string $mismatchReason = null,
        ?User $actor = null,
        array $meta = []
    ): Payment {
        return DB::transaction(function () use ($payment, $verificationStatus, $reason, $mismatchReason, $actor, $meta) {
            $old = $payment->status;

            $payment->update([
                'status' => Payment::STATUS_INVALID,
                'verification_status' => $verificationStatus,
                'failure_reason' => $reason,
                'mismatch_reason' => $mismatchReason,
                'paid_amount' => $meta['received'] ?? $meta['amount'] ?? $payment->paid_amount,
                'amount_received' => $meta['received'] ?? $payment->amount_received,
            ]);

            // Do not mark booking paid
            if ($payment->booking->payment_status === Booking::PAYMENT_STATUS_PAID) {
                $hasValidPaid = $payment->booking->payments()
                    ->where('id', '!=', $payment->id)
                    ->where('status', Payment::STATUS_PAID)
                    ->whereIn('verification_status', [
                        Payment::VERIFICATION_VERIFIED,
                        Payment::VERIFICATION_MANUALLY_CONFIRMED,
                    ])
                    ->exists();

                if (!$hasValidPaid) {
                    $payment->booking->update([
                        'payment_status' => Booking::PAYMENT_STATUS_FAILED,
                    ]);
                }
            }

            if ($actor) {
                $action = match ($verificationStatus) {
                    Payment::VERIFICATION_AMOUNT_MISMATCH => 'payment_amount_mismatch',
                    Payment::VERIFICATION_CURRENCY_MISMATCH => 'payment_currency_mismatch',
                    Payment::VERIFICATION_REFERENCE_MISMATCH => 'payment_reference_mismatch',
                    default => 'payment_invalid',
                };

                $this->auditLogService->log(
                    $actor,
                    $action,
                    'payment',
                    $payment->id,
                    ['status' => $old],
                    [
                        'status' => Payment::STATUS_INVALID,
                        'verification_status' => $verificationStatus,
                        'reason' => $reason,
                        'meta' => $meta,
                    ],
                    $reason,
                    $payment->branch_id
                );
            }

            Log::warning('[Payment] Marked invalid', [
                'payment_id' => $payment->id,
                'verification_status' => $verificationStatus,
                'reason' => $reason,
            ]);

            return $payment->fresh()->load('booking');
        });
    }

    public function markAsFailed(
        Payment $payment,
        ?string $reason = null,
        string $verificationStatus = Payment::VERIFICATION_GATEWAY_FAILED
    ): Payment {
        return DB::transaction(function () use ($payment, $reason, $verificationStatus) {
            if (in_array($payment->status, [
                Payment::STATUS_FAILED,
                Payment::STATUS_PAID,
                Payment::STATUS_REFUNDED,
                Payment::STATUS_INVALID,
            ], true)) {
                return $payment->fresh();
            }

            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => $reason,
                'verification_status' => $verificationStatus,
            ]);

            $hasPaid = $payment->booking->payments()
                ->where('status', Payment::STATUS_PAID)
                ->whereIn('verification_status', [
                    Payment::VERIFICATION_VERIFIED,
                    Payment::VERIFICATION_MANUALLY_CONFIRMED,
                ])
                ->exists();

            if (!$hasPaid) {
                $payment->booking->update([
                    'payment_status' => Booking::PAYMENT_STATUS_FAILED,
                ]);
            }

            event(new PaymentFailed($payment->booking, $payment));

            return $payment->fresh();
        });
    }

    public function refundPayment(Payment $payment, ?User $actor = null, ?float $refundAmount = null, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $refundAmount, $reason) {
            if (!in_array($payment->status, [
                Payment::STATUS_PAID,
                Payment::STATUS_REFUND_PENDING,
                Payment::STATUS_PARTIALLY_REFUNDED,
            ], true)) {
                throw new \InvalidArgumentException('Only paid payments can be refunded.');
            }

            $expected = (float) ($payment->expected_amount ?? $payment->amount);
            $alreadyRefunded = (float) ($payment->refund_amount ?? 0);
            $toRefund = $refundAmount !== null ? round($refundAmount, 2) : round($expected - $alreadyRefunded, 2);

            if ($toRefund <= 0) {
                throw new \InvalidArgumentException('Refund amount must be greater than zero.');
            }

            if ($toRefund + $alreadyRefunded > $expected + 0.01) {
                throw new \InvalidArgumentException('Refund amount exceeds paid amount.');
            }

            if ($actor) {
                $this->auditLogService->log(
                    $actor,
                    'payment_refund_requested',
                    'payment',
                    $payment->id,
                    ['status' => $payment->status],
                    ['refund_amount' => $toRefund, 'reason' => $reason],
                    $reason,
                    $payment->branch_id
                );
            }

            $payment->update([
                'status' => Payment::STATUS_REFUND_PENDING,
            ]);

            $totalRefunded = round($alreadyRefunded + $toRefund, 2);
            $newStatus = abs($totalRefunded - $expected) < 0.01
                ? Payment::STATUS_REFUNDED
                : Payment::STATUS_PARTIALLY_REFUNDED;

            $payment->update([
                'status' => $newStatus,
                'refund_amount' => $totalRefunded,
                'refunded_at' => now(),
                'failure_reason' => $reason,
            ]);

            if ($newStatus === Payment::STATUS_REFUNDED) {
                $payment->booking->update([
                    'payment_status' => Booking::PAYMENT_STATUS_REFUNDED,
                ]);
            }

            if ($actor) {
                $this->auditLogService->log(
                    $actor,
                    'payment_refunded',
                    'payment',
                    $payment->id,
                    null,
                    [
                        'status' => $newStatus,
                        'refund_amount' => $totalRefunded,
                    ],
                    $reason,
                    $payment->branch_id
                );
            }

            event(new PaymentRefunded($payment->booking, $payment));

            return $payment->fresh();
        });
    }

    /**
     * @return array{ok: bool, verification_status?: string, reason?: string, mismatch_reason?: string}
     */
    private function evaluateVerificationMatch(Payment $payment, array $verification): array
    {
        $storedRef = (string) $payment->transaction_reference;
        $gatewayRef = (string) ($verification['tx_ref'] ?? '');

        if ($gatewayRef !== '' && $gatewayRef !== $storedRef) {
            return [
                'ok' => false,
                'verification_status' => Payment::VERIFICATION_REFERENCE_MISMATCH,
                'reason' => "Transaction reference mismatch. Expected {$storedRef}, gateway reported {$gatewayRef}.",
            ];
        }

        $expectedCurrency = strtoupper((string) ($payment->currency ?: 'ETB'));
        $actualCurrency = strtoupper((string) ($verification['currency'] ?? 'ETB'));

        if ($actualCurrency !== '' && $expectedCurrency !== $actualCurrency) {
            return [
                'ok' => false,
                'verification_status' => Payment::VERIFICATION_CURRENCY_MISMATCH,
                'reason' => "Currency mismatch. Expected {$expectedCurrency}, received {$actualCurrency}.",
            ];
        }

        $expected = round((float) ($payment->expected_amount ?? $payment->amount), 2);
        $actual = round((float) ($verification['amount'] ?? 0), 2);

        if ($actual <= 0 || abs($expected - $actual) > 0.01) {
            $mismatch = $actual < $expected
                ? Payment::MISMATCH_UNDERPAYMENT
                : Payment::MISMATCH_OVERPAYMENT;

            return [
                'ok' => false,
                'verification_status' => Payment::VERIFICATION_AMOUNT_MISMATCH,
                'mismatch_reason' => $mismatch,
                'reason' => "The gateway reports a successful transaction, but the received amount does not match the booking amount. Expected {$expected}, received {$actual}.",
            ];
        }

        // Duplicate gateway transaction id across payments
        if (!empty($verification['reference'])) {
            $dup = Payment::where('gateway_reference', $verification['reference'])
                ->where('id', '!=', $payment->id)
                ->whereIn('status', Payment::SETTLED_STATUSES)
                ->exists();

            if ($dup) {
                return [
                    'ok' => false,
                    'verification_status' => Payment::VERIFICATION_REFERENCE_MISMATCH,
                    'reason' => 'Duplicate gateway transaction ID detected. Flagged for reconciliation.',
                    'mismatch_reason' => 'DUPLICATE_PAYMENT',
                ];
            }
        }

        return ['ok' => true];
    }

    private function assertNoOtherSettledPayment(Payment $payment): void
    {
        $other = Payment::where('booking_id', $payment->booking_id)
            ->where('id', '!=', $payment->id)
            ->where('status', Payment::STATUS_PAID)
            ->whereIn('verification_status', [
                Payment::VERIFICATION_VERIFIED,
                Payment::VERIFICATION_MANUALLY_CONFIRMED,
            ])
            ->exists();

        if ($other) {
            throw new \InvalidArgumentException(
                'This booking already has a verified paid payment. Additional settlement is blocked.'
            );
        }
    }

    private function sanitizeGatewayResponse(array $raw): array
    {
        // Keep reconciliation fields only — drop anything that looks like a secret
        $allowed = [
            'status', 'amount', 'currency', 'tx_ref', 'reference', 'created_at',
            'charge', 'method', 'type', 'first_name', 'last_name', 'email',
        ];

        return array_intersect_key($raw, array_flip($allowed));
    }

    /**
     * @return list<string>
     */
    public function allowedActions(Payment $payment, ?User $actor = null): array
    {
        $actions = ['view', 'view_history'];
        if (!$actor) {
            return $actions;
        }

        $sameBranch = $actor->isAdmin()
            || (int) $actor->branch_id === (int) $payment->branch_id;

        if (!$sameBranch && !$actor->isAdmin()) {
            return $actions;
        }

        if ($payment->isGatewayPayment()
            && $payment->transaction_reference
            && in_array($payment->status, [
                Payment::STATUS_PENDING,
                Payment::STATUS_PROCESSING,
                Payment::STATUS_FAILED,
            ], true)
            && !$payment->isVerified()) {
            $actions[] = 'verify_chapa';
        }

        if ($payment->payment_method === Payment::METHOD_CASH
            && $payment->status === Payment::STATUS_CASH_PENDING
            && ($actor->isAdmin() || $actor->isBranchManager() || $actor->isStaff())) {
            $actions[] = 'confirm_cash';
        }

        if ($payment->isMismatch()) {
            $actions[] = 'investigate';
        }

        if ($actor->isAdmin() && $payment->status === Payment::STATUS_PAID && $payment->isVerified()) {
            $actions[] = 'refund';
        }

        if ($actor->isAdmin() && in_array($payment->status, [
            Payment::STATUS_FAILED,
            Payment::STATUS_CANCELLED,
            Payment::STATUS_EXPIRED,
            Payment::STATUS_UNPAID,
            Payment::STATUS_PENDING,
            Payment::STATUS_INVALID,
        ], true)) {
            $actions[] = 'archive';
        }

        return array_values(array_unique($actions));
    }

    public function summaryCounts(?User $user = null): array
    {
        $query = Payment::query()->activeRecords();

        if ($user && !$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        $base = clone $query;

        return [
            'total' => (clone $base)->count(),
            'paid' => (clone $base)->where('status', Payment::STATUS_PAID)->count(),
            'processing' => (clone $base)->where('status', Payment::STATUS_PROCESSING)->count(),
            'pending' => (clone $base)->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_CASH_PENDING])->count(),
            'failed' => (clone $base)->where('status', Payment::STATUS_FAILED)->count(),
            'invalid' => (clone $base)->where('status', Payment::STATUS_INVALID)->count(),
            'amount_mismatch' => (clone $base)->where('verification_status', Payment::VERIFICATION_AMOUNT_MISMATCH)->count(),
            'refund_pending' => (clone $base)->where('status', Payment::STATUS_REFUND_PENDING)->count(),
            'refunded' => (clone $base)->whereIn('status', [Payment::STATUS_REFUNDED, Payment::STATUS_PARTIALLY_REFUNDED])->count(),
            'disputed' => (clone $base)->where('status', Payment::STATUS_DISPUTED)->count(),
            'cash_awaiting' => (clone $base)->where('status', Payment::STATUS_CASH_PENDING)->count(),
        ];
    }

    public function reconciliation(?User $user = null, array $filters = []): array
    {
        $query = Payment::with(['booking.vehicle', 'booking.user', 'user', 'branch'])
            ->where(function ($q) {
                $q->where('status', Payment::STATUS_INVALID)
                    ->orWhereIn('verification_status', [
                        Payment::VERIFICATION_AMOUNT_MISMATCH,
                        Payment::VERIFICATION_CURRENCY_MISMATCH,
                        Payment::VERIFICATION_REFERENCE_MISMATCH,
                        Payment::VERIFICATION_ERROR,
                    ])
                    ->orWhere(function ($q2) {
                        $q2->where('status', Payment::STATUS_PAID)
                            ->whereNotIn('verification_status', [
                                Payment::VERIFICATION_VERIFIED,
                                Payment::VERIFICATION_MANUALLY_CONFIRMED,
                            ]);
                    })
                    ->orWhere(function ($q3) {
                        $q3->whereColumn('branch_id', '!=', DB::raw('(select branch_id from bookings where bookings.id = payments.booking_id)'));
                    });
            });

        if ($user && !$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if (!empty($filters['branch_id']) && $user?->isAdmin()) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $items = $query->orderByDesc('updated_at')->limit(100)->get();

        $totals = [
            'expected' => 0.0,
            'received' => 0.0,
            'difference' => 0.0,
            'count' => $items->count(),
        ];

        $rows = $items->map(function (Payment $p) use (&$totals) {
            $expected = (float) ($p->expected_amount ?? $p->amount);
            $received = (float) ($p->paid_amount ?? $p->amount_received ?? 0);
            $diff = round($received - $expected, 2);
            $totals['expected'] += $expected;
            $totals['received'] += $received;
            $totals['difference'] += $diff;

            return [
                'id' => $p->id,
                'booking_reference' => $p->booking?->booking_reference,
                'branch' => $p->branch?->name,
                'expected_amount' => $expected,
                'paid_amount' => $received,
                'difference' => $diff,
                'gateway' => $p->gateway,
                'gateway_status' => $p->gateway_status,
                'status' => $p->status,
                'verification_status' => $p->verification_status,
                'mismatch_reason' => $p->mismatch_reason,
                'tx_ref' => $p->transaction_reference,
            ];
        })->all();

        return [
            'totals' => $totals,
            'summary' => $this->summaryCounts($user),
            'items' => $rows,
        ];
    }

    private function nextAttemptNumber(Booking $booking): int
    {
        return ((int) $booking->payments()->max('attempt_number')) + 1;
    }

    private function validateBookingOwnership(Booking $booking, int $userId): void
    {
        if ($booking->user_id !== $userId) {
            throw new \InvalidArgumentException('Unauthorized payment for this booking.');
        }
    }

    private function validateBookingEligibleForPayment(Booking $booking): void
    {
        $status = $booking->normalizeStatus();

        if (in_array($status, [Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED, Booking::STATUS_EXPIRED], true)) {
            throw new \InvalidArgumentException('Cancelled or rejected bookings cannot be paid.');
        }

        if (!$booking->isBranchApproved()) {
            throw new \InvalidArgumentException('Branch approval is required before payment can be made.');
        }

        if (!$booking->isAdminApproved()) {
            throw new \InvalidArgumentException('Admin approval is required before payment can be made.');
        }

        if (!in_array($status, Booking::PAYABLE_STATUSES, true)) {
            throw new \InvalidArgumentException('This booking is not eligible for payment in its current state.');
        }

        if ($status === Booking::STATUS_PENDING_BRANCH_APPROVAL) {
            throw new \InvalidArgumentException('This booking is awaiting branch approval. Payment is not yet available.');
        }

        if ($booking->payment_status === Booking::PAYMENT_STATUS_NOT_REQUIRED) {
            throw new \InvalidArgumentException('Payment is not required for this booking yet.');
        }

        if ($booking->payment_status === Booking::PAYMENT_STATUS_PAID) {
            $hasVerified = $booking->payments()
                ->where('status', Payment::STATUS_PAID)
                ->whereIn('verification_status', [
                    Payment::VERIFICATION_VERIFIED,
                    Payment::VERIFICATION_MANUALLY_CONFIRMED,
                ])
                ->exists();

            if ($hasVerified) {
                throw new \InvalidArgumentException('This booking has already been paid.');
            }
        }
    }

    private function validateNoDuplicatePaidPayment(Booking $booking): void
    {
        if ($booking->payments()
            ->where('status', Payment::STATUS_PAID)
            ->whereIn('verification_status', [
                Payment::VERIFICATION_VERIFIED,
                Payment::VERIFICATION_MANUALLY_CONFIRMED,
            ])
            ->exists()) {
            throw new \InvalidArgumentException('This booking has already been paid.');
        }
    }

    private function cleanUpOrphanedPayments(Booking $booking): void
    {
        $booking->payments()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
            ->where('created_at', '<', now()->subHours(2))
            ->where('is_archived', false)
            ->update([
                'status' => Payment::STATUS_EXPIRED,
                'verification_status' => Payment::VERIFICATION_UNVERIFIED,
                'failure_reason' => 'Expired pending payment',
            ]);
    }

    private function validatePaymentAmount(array $data, Booking $booking): void
    {
        if (isset($data['amount']) && abs((float) $data['amount'] - (float) $booking->total_price) > 0.01) {
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
