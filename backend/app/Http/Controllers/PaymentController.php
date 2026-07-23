<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
