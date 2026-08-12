<?php

namespace App\Http\Controllers;

use App\Http\Requests\InitializePaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Exceptions\PaymentVerificationRetryableException;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->getPaymentsForUser($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully',
            'data' => PaymentResource::collection($payments),
            'summary' => $request->user()->isCustomer()
                ? null
                : $this->paymentService->summaryCounts($request->user()),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->processPayment(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => new PaymentResource($payment),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function initialize(InitializePaymentRequest $request): JsonResponse
    {
        try {
            $result = $this->paymentService->initializePayment(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'checkout_url' => $result['checkout_url'],
                    'tx_ref' => $result['tx_ref'],
                    'payment' => new PaymentResource($result['payment']),
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            Log::error('Payment initialization failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed. Please try again later.',
            ], 500);
        }
    }

    public function verify(Request $request, string $txRef): JsonResponse
    {
        try {
            $payment = $this->paymentService->verifyPayment(
                $txRef,
                $request->user(),
                'customer_return'
            );

            if ($request->user()->isCustomer() && (int) $payment->user_id !== (int) $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => $payment->status === Payment::STATUS_PAID
                    ? 'Payment verified successfully'
                    : 'Payment status retrieved',
                'data' => new PaymentResource($payment),
            ]);
        } catch (PaymentVerificationRetryableException $e) {
            $payment = Payment::where('transaction_reference', $txRef)->first();

            return response()->json([
                'success' => true,
                'message' => $e->getMessage(),
                'data' => $payment ? new PaymentResource($payment->load('booking')) : null,
                'retryable' => true,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Payment reference not found.',
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            Log::error('Payment verification failed', ['tx_ref' => $txRef, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please try again later.',
            ], 500);
        }
    }

    /**
     * Authoritative payment status for frontend polling.
     */
    public function paymentStatus(Request $request, Payment $payment): JsonResponse
    {
        Gate::authorize('view', $payment);

        $verify = $request->boolean('verify', true);
        $data = $this->paymentService->getPaymentStatus($payment, $verify);

        return response()->json([
            'success' => true,
            'message' => 'Payment status retrieved successfully',
            'data' => [
                'payment_id' => $data['payment_id'],
                'booking_id' => $data['booking_id'],
                'status' => $data['status'],
                'verification_status' => $data['verification_status'],
                'payment_method' => $data['payment_method'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'tx_ref' => $data['tx_ref'],
                'chapa_reference' => $data['chapa_reference'],
                'gateway_status' => $data['gateway_status'],
                'paid_at' => $data['paid_at'],
                'verified_at' => $data['verified_at'],
                'verification_source' => $data['verification_source'],
                'booking_status' => $data['booking_status'],
                'booking_payment_status' => $data['booking_payment_status'],
                'failure_reason' => $data['failure_reason'],
                'payment' => new PaymentResource($data['payment']),
            ],
        ]);
    }

    /**
     * Admin / branch / staff: verify a payment by ID via Chapa API.
     */
    public function verifyById(Request $request, Payment $payment): JsonResponse
    {
        Gate::authorize('update', $payment);

        $user = $request->user();

        if ($user->isBranchManager() || $user->isStaff()) {
            if ((int) $user->branch_id !== (int) $payment->branch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to manage this branch payment.',
                    'code' => 'BRANCH_PAYMENT_FORBIDDEN',
                ], 403);
            }
        }

        try {
            $payment = $this->paymentService->verifyPaymentById(
                $payment,
                $user,
                'manual_verify'
            );

            $message = match ($payment->verification_status) {
                Payment::VERIFICATION_VERIFIED => 'Payment verified with Chapa.',
                Payment::VERIFICATION_AMOUNT_MISMATCH => 'The gateway reports a successful transaction, but the received amount does not match the booking amount.',
                Payment::VERIFICATION_CURRENCY_MISMATCH => 'Payment currency does not match the expected currency.',
                Payment::VERIFICATION_REFERENCE_MISMATCH => 'Transaction reference mismatch.',
                Payment::VERIFICATION_GATEWAY_FAILED => 'Gateway payment failed.',
                Payment::VERIFICATION_GATEWAY_PENDING => 'Gateway payment is still processing.',
                Payment::VERIFICATION_ERROR => 'Unable to verify transaction with Chapa right now.',
                default => 'Payment status retrieved from Chapa.',
            };

            $code = match ($payment->verification_status) {
                Payment::VERIFICATION_AMOUNT_MISMATCH => 'PAYMENT_AMOUNT_MISMATCH',
                Payment::VERIFICATION_CURRENCY_MISMATCH => 'PAYMENT_CURRENCY_MISMATCH',
                Payment::VERIFICATION_REFERENCE_MISMATCH => 'PAYMENT_REFERENCE_MISMATCH',
                default => null,
            };

            $http = 200;

            return response()->json([
                'success' => $payment->status === Payment::STATUS_PAID && $payment->isVerified(),
                'message' => $message,
                'code' => $code,
                'data' => new PaymentResource($payment->load(['booking.vehicle', 'booking.user', 'branch', 'user'])),
            ], $http);
        } catch (PaymentVerificationRetryableException $e) {
            $payment->refresh();

            return response()->json([
                'success' => true,
                'message' => $e->getMessage(),
                'code' => 'VERIFICATION_TEMPORARILY_UNAVAILABLE',
                'retryable' => true,
                'data' => new PaymentResource($payment->load(['booking', 'branch', 'user'])),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'PAYMENT_VERIFICATION_ERROR',
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please try again later.',
                'code' => 'VERIFICATION_TEMPORARILY_UNAVAILABLE',
            ], 500);
        }
    }

    /**
     * Customer / staff: booking payment status — may auto-verify with Chapa.
     */
    public function bookingPaymentStatus(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        $allowed = $user->isAdmin()
            || (int) $user->id === (int) $booking->user_id
            || (($user->isBranchManager() || $user->isStaff()) && (int) $user->branch_id === (int) $booking->branch_id);

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $verify = $request->boolean('verify', true);
        $data = $this->paymentService->getBookingPaymentStatus($booking, $verify);

        return response()->json([
            'success' => true,
            'message' => 'Payment status retrieved successfully',
            'data' => [
                'booking_id' => $data['booking_id'],
                'booking_status' => $data['booking_status'],
                'booking_payment_status' => $data['booking_payment_status'],
                'payment_status' => $data['payment_status'],
                'verification_status' => $data['verification_status'] ?? $data['payment']?->verification_status,
                'payment_method' => $data['payment']?->payment_method,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'tx_ref' => $data['tx_ref'],
                'chapa_reference' => $data['chapa_reference'],
                'verified_at' => $data['verified_at'],
                'paid_at' => $data['paid_at'],
                'payment' => $data['payment'] ? new PaymentResource($data['payment']) : null,
            ],
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        try {
            $data = array_merge($request->query(), $request->all());

            if (empty($data['tx_ref']) && empty($data['trx_ref'])) {
                Log::warning('Payment callback received with invalid data', [
                    'keys' => array_keys($data),
                ]);

                return response()->json(['status' => 'error', 'message' => 'Invalid callback data'], 400);
            }

            $this->paymentService->handleCallback($data);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Payment callback handling failed', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('chapa-signature')
            ?? $request->header('x-chapa-signature');

        try {
            $this->paymentService->handleWebhook(
                $request->all(),
                $rawBody,
                $signature
            );

            return response()->json(['status' => 'success'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 401);
        } catch (\Exception $e) {
            Log::error('Webhook handling failed', ['error' => $e->getMessage()]);

            // Still 200 for unknown tx to avoid endless retries on bad data after auth
            return response()->json(['status' => 'error'], 500);
        }
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        Gate::authorize('view', $payment);

        $payment->load(['booking']);

        return response()->json([
            'success' => true,
            'message' => 'Payment retrieved successfully',
            'data' => new PaymentResource($payment),
        ]);
    }

    public function markAsFailed(Request $request, Payment $payment): JsonResponse
    {
        Gate::authorize('update', $payment);

        try {
            $payment = $this->paymentService->markAsFailed($payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as failed',
                'data' => new PaymentResource($payment),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function refund(Request $request, Payment $payment): JsonResponse
    {
        Gate::authorize('refund', $payment);

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $payment = $this->paymentService->refundPayment(
                $payment,
                $request->user(),
                isset($data['amount']) ? (float) $data['amount'] : null,
                $data['reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment refund recorded successfully',
                'data' => new PaymentResource($payment),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        Gate::authorize('delete', $payment);

        return response()->json([
            'success' => false,
            'message' => 'Payment records cannot be permanently deleted. Use archive for non-financial records or refund for paid payments.',
        ], 403);
    }

    /**
     * Staff / branch manager / admin: confirm cash payment received at branch.
     */
    public function confirmCash(Request $request, Payment $payment): JsonResponse
    {
        Gate::authorize('confirmCash', $payment);

        $data = $request->validate([
            'amount_received' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $payment = $this->paymentService->confirmCashPayment(
                $payment,
                $request->user(),
                isset($data['amount_received']) ? (float) $data['amount_received'] : null
            );

            $message = $payment->status === Payment::STATUS_INVALID
                ? 'Cash amount does not match the booking amount. Payment flagged for investigation.'
                : 'Cash payment confirmed successfully.';

            return response()->json([
                'success' => $payment->status === Payment::STATUS_PAID && $payment->isVerified(),
                'message' => $message,
                'code' => $payment->status === Payment::STATUS_INVALID ? 'PAYMENT_AMOUNT_MISMATCH' : null,
                'data' => new PaymentResource($payment->load(['booking.vehicle', 'booking.user', 'branch', 'user', 'confirmer'])),
            ]);
        } catch (\InvalidArgumentException $e) {
            $forbidden = str_contains(strtolower($e->getMessage()), 'authorized');

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $forbidden ? 'BRANCH_PAYMENT_FORBIDDEN' : 'PAYMENT_CONFIRMATION_ERROR',
            ], $forbidden ? 403 : 422);
        }
    }

    /**
     * Read-only payment history with filters.
     */
    public function history(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Payment::class);

        $filters = $request->only([
            'branch_id', 'status', 'payment_method', 'verification_status',
            'search', 'date_from', 'date_to', 'per_page',
        ]);

        $payments = $this->paymentService->getPaymentHistory($request->user(), $filters);

        return response()->json([
            'success' => true,
            'message' => 'Payment history retrieved successfully',
            'data' => PaymentResource::collection($payments),
            'summary' => $this->paymentService->summaryCounts($request->user()),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function reconciliation(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Payment::class);

        $user = $request->user();
        if (!$user->isAdmin() && !$user->isBranchManager()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view payment reconciliation.',
                'code' => 'BRANCH_PAYMENT_FORBIDDEN',
            ], 403);
        }

        $data = $this->paymentService->reconciliation(
            $user,
            $request->only(['branch_id', 'date_from', 'date_to'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment reconciliation retrieved successfully',
            'data' => $data,
        ]);
    }

    public function bookingAttempts(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();
        $allowed = $user->isAdmin()
            || (int) $user->id === (int) $booking->user_id
            || (($user->isBranchManager() || $user->isStaff()) && (int) $user->branch_id === (int) $booking->branch_id);

        if (!$allowed) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'code' => 'BRANCH_PAYMENT_FORBIDDEN',
            ], 403);
        }

        $attempts = $booking->payments()
            ->with(['user', 'branch', 'confirmer'])
            ->orderBy('attempt_number')
            ->get();

        $verifiedPaid = $attempts->first(fn ($p) => $p->isSettled());

        return response()->json([
            'success' => true,
            'data' => [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'amount_due' => (float) $booking->total_price,
                'amount_paid' => $verifiedPaid ? (float) ($verifiedPaid->paid_amount ?? $verifiedPaid->amount) : 0,
                'amount_remaining' => $verifiedPaid ? 0 : (float) $booking->total_price,
                'payment_status' => $booking->payment_status,
                'attempts' => PaymentResource::collection($attempts),
            ],
        ]);
    }
}
