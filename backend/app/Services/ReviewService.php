<?php

namespace App\Services;

use App\Events\ReviewCreated;
use App\Events\ReviewUpdated;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    public function getApprovedReviewsForVehicle(Vehicle $vehicle): LengthAwarePaginator
    {
        return Review::with('user')
            ->where('vehicle_id', $vehicle->id)
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getUserReviews(User $user): LengthAwarePaginator
    {
        return Review::with(['vehicle', 'booking'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function createReview(array $data, int $userId): Review
    {
        $vehicle = $this->findVehicleOrFail($data['vehicle_id']);
        $booking = $this->findBookingOrFail($data['booking_id']);

        $this->validateBookingBelongsToVehicle($booking, $vehicle);
        $this->validateBookingBelongsToUser($booking, $userId);
        $this->validateBookingCompleted($booking);
        $this->validateNoDuplicateReview($booking, $userId);
        $this->validateRating($data['rating']);

        $review = DB::transaction(function () use ($data, $userId, $vehicle, $booking) {
            return Review::create([
                'user_id' => $userId,
                'vehicle_id' => $vehicle->id,
                'booking_id' => $booking->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => Review::STATUS_APPROVED,
            ]);
        });

        $review->load('user', 'vehicle');

        event(new ReviewCreated($review));

        Log::info('Review created', [
            'review_id' => $review->id,
            'user_id' => $userId,
            'vehicle_id' => $vehicle->id,
            'rating' => $review->rating,
        ]);

        return $review;
    }

    public function updateReview(Review $review, array $data, int $userId): Review
    {
        $this->validateReviewOwnership($review, $userId);

        if (isset($data['rating'])) {
            $this->validateRating($data['rating']);
        }

        $review = DB::transaction(function () use ($review, $data) {
            $review->update([
                'rating' => $data['rating'] ?? $review->rating,
                'comment' => $data['comment'] ?? $review->comment,
            ]);

            return $review->fresh()->load('user', 'vehicle');
        });

        event(new ReviewUpdated($review));

        Log::info('Review updated', [
            'review_id' => $review->id,
            'user_id' => $userId,
        ]);

        return $review;
    }

    public function deleteReview(Review $review, int $userId): void
    {
        $this->validateReviewOwnership($review, $userId);

        $reviewId = $review->id;

        $review->delete();

        Log::info('Review deleted', [
            'review_id' => $reviewId,
            'user_id' => $userId,
        ]);
    }

    private function findVehicleOrFail(int $vehicleId): Vehicle
    {
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            throw new \InvalidArgumentException('The specified vehicle does not exist.');
        }

        return $vehicle;
    }

    private function findBookingOrFail(int $bookingId): Booking
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            throw new \InvalidArgumentException('The specified booking does not exist.');
        }

        return $booking;
    }

    private function validateBookingBelongsToVehicle(Booking $booking, Vehicle $vehicle): void
    {
        if ($booking->vehicle_id !== $vehicle->id) {
            throw new \InvalidArgumentException('This booking does not belong to the specified vehicle.');
        }
    }

    private function validateBookingBelongsToUser(Booking $booking, int $userId): void
    {
        if ($booking->user_id !== $userId) {
            throw new \InvalidArgumentException('You can only review your own bookings.');
        }
    }

    private function validateBookingCompleted(Booking $booking): void
    {
        if ($booking->status !== Booking::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Only completed bookings can be reviewed.');
        }
    }

    private function validateNoDuplicateReview(Booking $booking, int $userId): void
    {
        $existingReview = Review::where('booking_id', $booking->id)
            ->where('user_id', $userId)
            ->exists();

        if ($existingReview) {
            throw new \InvalidArgumentException('You have already reviewed this booking.');
        }
    }

    private function validateReviewOwnership(Review $review, int $userId): void
    {
        if ($review->user_id !== $userId) {
            throw new \InvalidArgumentException('You can only manage your own reviews.');
        }
    }

    private function validateRating(int $rating): void
    {
        if ($rating < Review::MIN_RATING || $rating > Review::MAX_RATING) {
            throw new \InvalidArgumentException(
                'Rating must be between ' . Review::MIN_RATING . ' and ' . Review::MAX_RATING . '.'
            );
        }
    }
}
