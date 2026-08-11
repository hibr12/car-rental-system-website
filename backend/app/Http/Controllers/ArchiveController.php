<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\ArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArchiveController extends Controller
{
    public function __construct(
        private ArchiveService $archiveService
    ) {}

    public function bookings(Request $request): JsonResponse
    {
        Gate::authorize('archiveAny', Booking::class);

        $filters = $request->only(['search', 'status', 'branch_id', 'per_page']);
        $bookings = $this->archiveService->getArchivedBookings($request->user(), $filters);

        return response()->json([
            'success' => true,
            'message' => 'Archived bookings retrieved successfully',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function archiveBooking(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('archive', $booking);

        try {
            $booking = $this->archiveService->archiveBooking(
                $booking,
                $request->user(),
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking archived successfully. The record is preserved and searchable in the archive.',
                'data' => new BookingResource($booking),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function payments(Request $request): JsonResponse
    {
        Gate::authorize('archiveAny', Payment::class);

        $filters = $request->only(['search', 'status', 'per_page']);
        $payments = $this->archiveService->getArchivedPayments($request->user(), $filters);

        return response()->json([
            'success' => true,
            'message' => 'Archived payments retrieved successfully',
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function archivePayment(Request $request, Payment $payment): JsonResponse
    {
        Gate::authorize('archive', $payment);

        try {
            $payment = $this->archiveService->archivePayment(
                $payment,
                $request->user(),
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment archived successfully. Financial history is preserved in the database.',
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
