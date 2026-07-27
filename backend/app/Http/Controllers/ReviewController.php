<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
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
                'average_rating' => (float) $vehicle->averageRating(),
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
                'message' => 'Review created successfully',
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
                $request->user()->id
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

    public function destroy(Request $request, Review $review): JsonResponse
    {
        Gate::authorize('delete', $review);

        try {
            $this->reviewService->deleteReview($review, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
