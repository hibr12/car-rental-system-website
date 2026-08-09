<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Booking::with(['vehicle', 'user']);

        if ($user->isAdmin() || $user->isFleetManager()) {
            // Admin and Fleet Manager see all bookings
        } elseif ($user->isBranchManager() || $user->isStaff()) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->where('user_id', $user->id);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Bookings retrieved successfully',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        if (!Gate::allows('view', $booking)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $booking->load(['vehicle', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Booking retrieved successfully',
            'data' => new BookingResource($booking),
        ]);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->createBooking(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => new BookingResource($booking),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        if (!Gate::allows('cancel', $booking)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        try {
            $booking = $this->bookingService->cancelBooking($booking);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => new BookingResource($booking),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirm(Request $request, Booking $booking): JsonResponse
    {
        try {
            $booking = $this->bookingService->confirmBooking($booking);

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed successfully',
                'data' => new BookingResource($booking),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reject(Request $request, Booking $booking): JsonResponse
    {
        try {
            $booking = $this->bookingService->rejectBooking($booking, $request->input('reason'));

            return response()->json([
                'success' => true,
                'message' => 'Booking rejected successfully',
                'data' => new BookingResource($booking),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function pickup(Request $request, Booking $booking): JsonResponse
    {
        try {
            $booking = $this->bookingService->markAsPickedUp($booking);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle picked up successfully',
                'data' => new BookingResource($booking),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function returnVehicle(Request $request, Booking $booking): JsonResponse
    {
        try {
            $booking = $this->bookingService->markAsReturned($booking);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle returned successfully',
                'data' => new BookingResource($booking),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function adminIndex(Request $request): JsonResponse
    {
        if (!Gate::allows('manageAll', Booking::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $user = $request->user();
        $query = Booking::with(['vehicle', 'user']);

        if ($user->isBranchManager() || $user->isStaff()) {
            $query->where('branch_id', $user->branch_id);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'All bookings retrieved successfully',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }
}