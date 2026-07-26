<?php

namespace App\Http\Controllers;

use App\Http\Requests\InitializePaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

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
            $payment = $this->paymentService->verifyPayment($txRef);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => new PaymentResource($payment),
            ]);
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

    public function callback(Request $request): JsonResponse
    {
        try {
            $this->paymentService->handleCallback($request->all());

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Payment callback handling failed', ['error' => $e->getMessage()]);

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

        try {
            $payment = $this->paymentService->refundPayment($payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment refunded successfully',
                'data' => new PaymentResource($payment),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
