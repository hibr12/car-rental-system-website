<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function index(Vehicle $vehicle): JsonResponse
    {
        $reviews = Review::with('user')
            ->where('vehicle_id', $vehicle->id)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

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

    public function store(StoreReviewRequest $request, Vehicle $vehicle): JsonResponse
    {
        if (!Gate::allows('create', Review::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Only customers can create reviews.',
            ], 403);
        }

        $booking = Booking::find($request->booking_id);

        if (!$booking || $booking->vehicle_id !== $vehicle->id) {
            return response()->json([
                'success' => false,
                'message' => 'This booking does not belong to the specified vehicle.',
            ], 422);
        }

        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only review your own bookings.',
            ], 403);
        }

        if ($booking->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed bookings can be reviewed.',
            ], 422);
        }

        if (Review::where('booking_id', $booking->id)->where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this booking.',
            ], 422);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'vehicle_id' => $vehicle->id,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'approved',
        ]);

        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => new ReviewResource($review),
        ], 201);
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        if (!Gate::allows('delete', $review)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ]);
    }
}