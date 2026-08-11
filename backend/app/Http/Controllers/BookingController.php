<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\CheckAvailabilityRequest;
use App\Http\Requests\PriceEstimateRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\BookingService;
use Carbon\Carbon;
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
        $bookings = Booking::with([
                'vehicle.category',
                'vehicle.images',
                'vehicle.primaryImage',
                'user',
            ])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

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
        Gate::authorize('view', $booking);

        $booking->load([
            'vehicle.category',
            'vehicle.images',
            'vehicle.primaryImage',
            'user',
        ]);

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

    public function cancel(CancelBookingRequest $request, Booking $booking): JsonResponse
    {
        Gate::authorize('cancel', $booking);

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
        Gate::authorize('confirm', Booking::class);

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
        Gate::authorize('reject', Booking::class);

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
        Gate::authorize('pickup', Booking::class);

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
        Gate::authorize('returnVehicle', Booking::class);

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
        Gate::authorize('manageAll', Booking::class);

        $user  = $request->user();
        $query = Booking::with([
            'vehicle.category',
            'vehicle.images',
            'vehicle.primaryImage',
            'user',
            'branch',
        ]);

        // Branch managers / staff only see their branch bookings
        if (!$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id') && $user->isAdmin()) {
            $query->where('branch_id', $request->branch_id);
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

    public function checkAvailability(CheckAvailabilityRequest $request): JsonResponse
    {
        $vehicle = Vehicle::find($request->vehicle_id);

        if (!$vehicle || $vehicle->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle is not available.',
                'data' => ['available' => false],
            ]);
        }

        $pickupDate = Carbon::parse($request->pickup_date);
        $returnDate = Carbon::parse($request->return_date);
        $hasOverlap = $this->bookingService->hasOverlap($vehicle->id, $pickupDate, $returnDate);

        return response()->json([
            'success' => true,
            'message' => $hasOverlap ? 'Vehicle is not available for the selected dates.' : 'Vehicle is available.',
            'data' => ['available' => !$hasOverlap],
        ]);
    }

    public function priceEstimate(PriceEstimateRequest $request): JsonResponse
    {
        $vehicle = Vehicle::find($request->vehicle_id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found.',
            ], 404);
        }

        $pickupDate = Carbon::parse($request->pickup_date);
        $returnDate = Carbon::parse($request->return_date);

        $breakdown = $this->bookingService->calculatePriceBreakdown(
            $vehicle,
            $pickupDate,
            $returnDate,
            (float) ($request->additional_charges ?? 0),
            (float) ($request->discount ?? 0)
        );

        return response()->json([
            'success' => true,
            'message' => 'Price estimate calculated successfully.',
            'data' => [
                'vehicle_id' => $breakdown['vehicle_id'],
                'price_per_day' => number_format($breakdown['price_per_day'], 2),
                'number_of_days' => $breakdown['number_of_days'],
                'subtotal' => number_format($breakdown['subtotal'], 2),
                'additional_charges' => number_format($breakdown['additional_charges'], 2),
                'discount' => number_format($breakdown['discount'], 2),
                'total_price' => number_format($breakdown['total_price'], 2),
            ],
        ]);
    }
}