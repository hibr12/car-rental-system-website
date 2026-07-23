<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelBookingRequest;
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
        $bookings = Booking::with(['vehicle', 'user'])
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

        $bookings = Booking::with(['vehicle', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

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

    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'pickup_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after:pickup_date',
        ]);

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

    public function priceEstimate(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'pickup_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after:pickup_date',
            'additional_charges' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $vehicle = Vehicle::find($request->vehicle_id);

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found.',
            ], 404);
        }

        $pickupDate = Carbon::parse($request->pickup_date);
        $returnDate = Carbon::parse($request->return_date);
        $numberOfDays = max(1, $pickupDate->diffInDays($returnDate));
        $pricePerDay = (float) $vehicle->rental_price_per_day;
        $subtotal = $numberOfDays * $pricePerDay;
        $additionalCharges = (float) ($request->additional_charges ?? 0);
        $discount = (float) ($request->discount ?? 0);
        $totalPrice = $subtotal + $additionalCharges - $discount;

        return response()->json([
            'success' => true,
            'message' => 'Price estimate calculated successfully.',
            'data' => [
                'vehicle_id' => $vehicle->id,
                'price_per_day' => number_format($pricePerDay, 2),
                'number_of_days' => $numberOfDays,
                'subtotal' => number_format($subtotal, 2),
                'additional_charges' => number_format($additionalCharges, 2),
                'discount' => number_format($discount, 2),
                'total_price' => number_format($totalPrice, 2),
            ],
        ]);
    }
}