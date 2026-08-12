<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminReviewResponseRequest;
use App\Http\Requests\AdminReviewStatusRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Review;
use App\Models\Vehicle;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService
    ) {}

    public function index(Vehicle $vehicle): JsonResponse
    {
        $reviews = $this->reviewService->getApprovedReviewsForVehicle($vehicle);

        return response()->json([
            'success' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'average_rating' => round((float) $vehicle->averageRating(), 1),
            ],
        ]);
    }

    public function branchIndex(Branch $branch): JsonResponse
    {
        $reviews = $this->reviewService->getReviewsForBranch($branch->id);

        return response()->json([
            'success' => true,
            'message' => 'Branch reviews retrieved successfully',
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'average_rating' => round((float) Review::where('branch_id', $branch->id)
                    ->publiclyVisible()
                    ->avg('overall_rating'), 1),
            ],
        ]);
    }

    public function userReviews(Request $request): JsonResponse
    {
        $reviews = $this->reviewService->getUserReviews($request->user());

        return response()->json([
            'success' => true,
            'message' => 'User reviews retrieved successfully',
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function eligibleBookings(Request $request): JsonResponse
    {
        $bookings = $this->reviewService->getEligibleBookings($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Eligible bookings retrieved successfully',
            'data' => $bookings->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'pickup_date' => $booking->pickup_date?->toISOString(),
                'return_date' => $booking->return_date?->toISOString(),
                'returned_at' => $booking->returned_at?->toISOString(),
                'status' => $booking->status,
                'vehicle' => $booking->vehicle ? [
                    'id' => $booking->vehicle->id,
                    'brand' => $booking->vehicle->brand,
                    'model' => $booking->vehicle->model,
                ] : null,
                'branch' => $booking->branch ? [
                    'id' => $booking->branch->id,
                    'name' => $booking->branch->name,
                ] : null,
            ]),
        ]);
    }

    public function eligibility(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this booking.',
            ], 403);
        }

        $result = $this->reviewService->getReviewEligibility($booking, $request->user());

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function show(Request $request, Review $review): JsonResponse
    {
        Gate::authorize('view', $review);

        $review->load(['user', 'vehicle', 'booking', 'branch', 'adminResponder']);

        return response()->json([
            'success' => true,
            'data' => new ReviewResource($review),
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        Gate::authorize('viewAnyAdmin', Review::class);

        $filters = $request->only(['search', 'rating', 'branch_id', 'status']);
        $perPage = (int) $request->input('per_page', 10);

        $reviews = $this->reviewService->getAdminReviews($request->user(), $filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function adminStats(Request $request): JsonResponse
    {
        Gate::authorize('viewAnyAdmin', Review::class);

        $filters = $request->only(['branch_id']);
        $stats = $this->reviewService->getAdminStats($request->user(), $filters);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function storeForBooking(StoreReviewRequest $request, Booking $booking): JsonResponse
    {
        Gate::authorize('create', Review::class);

        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to review this booking.',
            ], 403);
        }

        try {
            $review = $this->reviewService->createReviewForBooking(
                $booking,
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully',
                'data' => new ReviewResource($review),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(StoreReviewRequest $request, Vehicle $vehicle): JsonResponse
    {
        Gate::authorize('create', Review::class);

        try {
            $review = $this->reviewService->createReview(
                array_merge($request->validated(), ['vehicle_id' => $vehicle->id]),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully',
                'data' => new ReviewResource($review),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        Gate::authorize('update', $review);

        try {
            $review = $this->reviewService->updateReview(
                $review,
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully',
                'data' => new ReviewResource($review),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateStatus(AdminReviewStatusRequest $request, Review $review): JsonResponse
    {
        Gate::authorize('moderate', $review);

        try {
            $review = $this->reviewService->updateStatus(
                $review,
                $request->validated('status'),
                $request->user(),
                $request->validated('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Review status updated successfully',
                'data' => new ReviewResource($review),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function respond(AdminReviewResponseRequest $request, Review $review): JsonResponse
    {
        Gate::authorize('respond', $review);

        try {
            $review = $this->reviewService->addAdminResponse(
                $review,
                $request->validated('admin_response'),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Response submitted successfully',
                'data' => new ReviewResource($review),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        Gate::authorize('archive', $review);

        try {
            $review = $this->reviewService->archiveReview(
                $review,
                $request->user(),
                'Archived via API'
            );

            return response()->json([
                'success' => true,
                'message' => 'Review archived successfully',
                'data' => new ReviewResource($review),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
