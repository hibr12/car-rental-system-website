<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\CheckAvailabilityRequest;
use App\Http\Requests\PriceEstimateRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\AuditLogService;
use App\Services\BookingService;
use App\Services\BookingWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private BookingWorkflowService $workflow,
        private AuditLogService $auditLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with([
                'vehicle.category',
                'vehicle.images',
                'vehicle.primaryImage',
                'user',
                'branch',
                'payments',
                'review',
            ])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

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
        Gate::authorize('view', $booking);

        $booking->load([
            'vehicle.category',
            'vehicle.images',
            'vehicle.primaryImage',
            'user',
            'branch',
            'payments',
            'review',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking retrieved successfully',
            'data' => new BookingResource($booking),
            'audit' => $this->auditLogService->forEntity('booking', $booking->id),
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
                'data' => new BookingResource($booking->load(['payments', 'review'])),
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
            $booking = $this->bookingService->cancelBooking(
                $booking,
                $request->user(),
                $request->input('reason'),
                $request->user()->isCustomer() ? 'customer' : 'staff'
            );

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
        Gate::authorize('confirm', $booking);

        try {
            $booking = $this->bookingService->confirmBooking(
                $booking,
                $request->user()
            );

            $message = match ($booking->normalizeStatus()) {
                Booking::STATUS_PENDING_ADMIN_APPROVAL => 'Branch approved. Awaiting admin approval.',
                Booking::STATUS_CONFIRMED, Booking::STATUS_READY_FOR_PICKUP => 'Booking confirmed successfully.',
                default => 'Booking approval recorded successfully.',
            };

            return response()->json([
                'success' => true,
                'message' => $message,
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
        Gate::authorize('reject', $booking);

        $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        try {
            $booking = $this->bookingService->rejectBooking(
                $booking,
                $request->input('reason'),
                $request->user()
            );

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

    public function preparePickup(Request $request, Booking $booking): JsonResponse
    {
        Gate::authorize('preparePickup', $booking);

        try {
            $booking = $this->bookingService->preparePickup($booking, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Booking marked ready for pickup.',
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
        Gate::authorize('pickup', $booking);

        $data = $request->validate([
            'identity_verification_status' => ['nullable', 'string', 'in:verified,unverified,not_required'],
            'license_verification_status' => ['nullable', 'string', 'in:verified,unverified,not_required'],
            'pickup_mileage' => ['nullable', 'integer', 'min:0'],
            'pickup_fuel_level' => ['nullable', 'string', 'in:empty,quarter,half,three_quarter,full'],
            'exterior_condition' => ['nullable', 'string', 'max:50'],
            'interior_condition' => ['nullable', 'string', 'max:50'],
            'existing_damage' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'skip_document_check' => ['nullable', 'boolean'],
        ]);

        try {
            $booking = $this->bookingService->markAsPickedUp($booking, $request->user(), $data);

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
        Gate::authorize('returnVehicle', $booking);

        $data = $request->validate([
            'return_mileage' => ['nullable', 'integer', 'min:0'],
            'return_fuel_level' => ['nullable', 'string', 'in:empty,quarter,half,three_quarter,full'],
            'exterior_condition' => ['nullable', 'string', 'max:50'],
            'interior_condition' => ['nullable', 'string', 'max:50'],
            'new_damage' => ['nullable', 'string', 'max:2000'],
            'damage_notes' => ['nullable', 'string', 'max:2000'],
            'additional_charges' => ['nullable', 'numeric', 'min:0'],
            'requires_maintenance' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $booking = $this->bookingService->markAsReturned($booking, $request->user(), $data);

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

        $user = $request->user();
        $query = Booking::with([
            'vehicle.category',
            'vehicle.images',
            'vehicle.primaryImage',
            'user',
            'branch',
            'payments',
            'review',
        ])->activeRecords();

        if (!$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->filled('branch_approval_status')) {
            $query->where('branch_approval_status', $request->branch_approval_status);
        }

        if ($request->filled('admin_approval_status')) {
            $query->where('admin_approval_status', $request->admin_approval_status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'pending') {
                $query->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_PENDING_PAYMENT]);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('branch_id') && $user->isAdmin()) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('user_id', $request->customer_id);
        }

        if ($request->filled('pickup_date_from')) {
            $query->whereDate('pickup_date', '>=', $request->pickup_date_from);
        }

        if ($request->filled('pickup_date_to')) {
            $query->whereDate('pickup_date', '<=', $request->pickup_date_to);
        }

        if ($request->filled('return_date_from')) {
            $query->whereDate('return_date', '>=', $request->return_date_from);
        }

        if ($request->filled('return_date_to')) {
            $query->whereDate('return_date', '<=', $request->return_date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($vq) use ($search) {
                        $vq->where('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('registration_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('branch', function ($bq) use ($search) {
                        $bq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('payments', function ($pq) use ($search) {
                        $pq->where('transaction_reference', 'like', "%{$search}%");
                    });
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'All bookings retrieved successfully',
            'data' => BookingResource::collection($bookings),
            'summary' => $this->workflow->summaryCounts($user, $request->filled('branch_id') ? (int) $request->branch_id : null),
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
