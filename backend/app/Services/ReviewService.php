<?php

namespace App\Services;

use App\Events\ReviewCreated;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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

    public function createReview(array $data, int $userId): Review
    {
        return DB::transaction(function () use ($data, $userId) {
            $vehicle = $this->findVehicleOrFail($data['vehicle_id']);

            $booking = $this->findBookingOrFail($data['booking_id']);

            $this->validateBookingBelongsToVehicle($booking, $vehicle);
            $this->validateBookingBelongsToUser($booking, $userId);
            $this->validateBookingCompleted($booking);
            $this->validateNoDuplicateReview($booking, $userId);
            $this->validateRating($data['rating']);

            $review = Review::create([
                'user_id' => $userId,
                'vehicle_id' => $vehicle->id,
                'booking_id' => $booking->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => Review::STATUS_APPROVED,
            ]);

            $review->load('user', 'vehicle');

            event(new ReviewCreated($review));

            return $review;
        });
    }

    public function deleteReview(Review $review): void
    {
        $review->delete();
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

    private function validateRating(int $rating): void
    {
        if ($rating < Review::MIN_RATING || $rating > Review::MAX_RATING) {
            throw new \InvalidArgumentException(
                'Rating must be between ' . Review::MIN_RATING . ' and ' . Review::MAX_RATING . '.'
            );
        }
    }
}
