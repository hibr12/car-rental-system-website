<?php

namespace App\Services;

use App\Events\ReviewCreated;
use App\Events\ReviewUpdated;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    public function __construct(
        private AuditLogService $auditLog
    ) {}

    public function getApprovedReviewsForVehicle(Vehicle $vehicle, int $perPage = 10): LengthAwarePaginator
    {
        return Review::with(['user:id,name', 'branch:id,name,code'])
            ->where('vehicle_id', $vehicle->id)
            ->publiclyVisible()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getReviewsForBranch(int $branchId, int $perPage = 10): LengthAwarePaginator
    {
        return Review::with(['user:id,name', 'vehicle:id,brand,model'])
            ->where('branch_id', $branchId)
            ->publiclyVisible()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getUserReviews(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Review::with(['vehicle', 'booking', 'branch'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getEligibleBookings(User $user): Collection
    {
        return Booking::with(['vehicle', 'branch', 'review'])
            ->where('user_id', $user->id)
            ->where('status', Booking::STATUS_COMPLETED)
            ->whereNotNull('picked_up_at')
            ->whereNotNull('returned_at')
            ->whereDoesntHave('review')
            ->orderByDesc('returned_at')
            ->get();
    }

    public function getReviewEligibility(Booking $booking, User $user): array
    {
        try {
            $this->validateReviewEligibility($booking, $user->id);
            $this->validateNoDuplicateReview($booking, $user->id);

            return [
                'eligible' => true,
                'message' => 'You can review this rental.',
            ];
        } catch (\InvalidArgumentException $e) {
            $existing = Review::where('booking_id', $booking->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                return [
                    'eligible' => false,
                    'already_reviewed' => true,
                    'review_id' => $existing->id,
                    'message' => 'You have already reviewed this rental.',
                ];
            }

            return [
                'eligible' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getAdminReviews(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Review::with(['user', 'vehicle', 'booking', 'branch', 'adminResponder'])
            ->orderByDesc('created_at');

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        } elseif (!empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (!empty($filters['rating'])) {
            $query->where('overall_rating', (int) $filters['rating']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', function ($vq) use ($search) {
                    $vq->whereRaw('LOWER(brand) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(model) LIKE ?', ["%{$search}%"]);
                })->orWhereHas('user', function ($uq) use ($search) {
                    $uq->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                })->orWhereHas('booking', function ($bq) use ($search) {
                    $bq->whereRaw('LOWER(booking_reference) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        return $query->paginate($perPage);
    }

    public function getAdminStats(User $user, array $filters = []): array
    {
        $query = Review::query();

        if ($user->isBranchManager() && !$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        } elseif (!empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        $base = clone $query;

        return [
            'total' => (clone $base)->count(),
            'published' => (clone $base)->where('status', Review::STATUS_PUBLISHED)->count(),
            'flagged' => (clone $base)->where('status', Review::STATUS_FLAGGED)->count(),
            'hidden' => (clone $base)->where('status', Review::STATUS_HIDDEN)->count(),
            'archived' => (clone $base)->where('status', Review::STATUS_ARCHIVED)->count(),
            'average_rating' => round((float) ((clone $base)->where('status', Review::STATUS_PUBLISHED)->avg('overall_rating') ?? 0), 1),
            'five_star' => (clone $base)->where('overall_rating', 5)->count(),
            'four_star' => (clone $base)->where('overall_rating', 4)->count(),
            'three_star' => (clone $base)->where('overall_rating', 3)->count(),
            'two_star' => (clone $base)->where('overall_rating', 2)->count(),
            'one_star' => (clone $base)->where('overall_rating', 1)->count(),
        ];
    }

    public function createReviewForBooking(Booking $booking, array $data, int $userId): Review
    {
        $this->validateReviewEligibility($booking, $userId);
        $this->validateNoDuplicateReview($booking, $userId);
        $this->validateRatings($data);

        $review = DB::transaction(function () use ($data, $userId, $booking) {
            return Review::create([
                'user_id' => $userId,
                'vehicle_id' => $booking->vehicle_id,
                'booking_id' => $booking->id,
                'branch_id' => $booking->branch_id,
                'overall_rating' => $data['overall_rating'],
                'vehicle_rating' => $data['vehicle_rating'],
                'cleanliness_rating' => $data['cleanliness_rating'],
                'staff_rating' => $data['staff_rating'],
                'value_rating' => $data['value_rating'],
                'comment' => $this->sanitizeComment($data['comment'] ?? null),
                'status' => Review::STATUS_PUBLISHED,
            ]);
        });

        $review->load(['user', 'vehicle', 'booking', 'branch']);

        event(new ReviewCreated($review));

        Log::info('Review created', [
            'review_id' => $review->id,
            'user_id' => $userId,
            'booking_id' => $booking->id,
            'overall_rating' => $review->overall_rating,
        ]);

        return $review;
    }

    /** @deprecated Legacy vehicle-scoped create — derives IDs from booking */
    public function createReview(array $data, int $userId): Review
    {
        $booking = $this->findBookingOrFail($data['booking_id']);
        $vehicle = $this->findVehicleOrFail($data['vehicle_id'] ?? $booking->vehicle_id);

        if ($booking->vehicle_id !== $vehicle->id) {
            throw new \InvalidArgumentException('This booking does not belong to the specified vehicle.');
        }

        $normalized = [
            'overall_rating' => $data['overall_rating'] ?? $data['rating'] ?? null,
            'vehicle_rating' => $data['vehicle_rating'] ?? $data['overall_rating'] ?? $data['rating'] ?? null,
            'cleanliness_rating' => $data['cleanliness_rating'] ?? $data['overall_rating'] ?? $data['rating'] ?? null,
            'staff_rating' => $data['staff_rating'] ?? $data['overall_rating'] ?? $data['rating'] ?? null,
            'value_rating' => $data['value_rating'] ?? $data['overall_rating'] ?? $data['rating'] ?? null,
            'comment' => $data['comment'] ?? null,
        ];

        return $this->createReviewForBooking($booking, $normalized, $userId);
    }

    public function updateReview(Review $review, array $data, User $user): Review
    {
        if (!$user->isAdmin() && $review->user_id !== $user->id) {
            throw new \InvalidArgumentException('You can only manage your own reviews.');
        }

        if (!$user->isAdmin() && !$review->isEditableByCustomer()) {
            throw new \InvalidArgumentException('Reviews can only be edited within ' . Review::EDIT_WINDOW_HOURS . ' hours of submission.');
        }

        $this->validateRatings($data, required: false);

        $review = DB::transaction(function () use ($review, $data) {
            $updates = [];

            foreach (['overall_rating', 'vehicle_rating', 'cleanliness_rating', 'staff_rating', 'value_rating'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if (array_key_exists('comment', $data)) {
                $updates['comment'] = $this->sanitizeComment($data['comment']);
            }

            $review->update($updates);

            return $review->fresh()->load(['user', 'vehicle', 'booking', 'branch']);
        });

        event(new ReviewUpdated($review));

        Log::info('Review updated', [
            'review_id' => $review->id,
            'user_id' => $user->id,
        ]);

        return $review;
    }

    public function updateStatus(Review $review, string $status, User $actor, ?string $reason = null): Review
    {
        if (!in_array($status, Review::STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid review status.');
        }

        if ($actor->isBranchManager() && !$actor->isAdmin() && $review->branch_id !== $actor->branch_id) {
            throw new \InvalidArgumentException('You can only moderate reviews for your branch.');
        }

        $oldStatus = $review->status;

        $review->update(['status' => $status]);

        $this->auditLog->log(
            $actor,
            'review_status_updated',
            'review',
            $review->id,
            ['status' => $oldStatus],
            ['status' => $status],
            $reason,
            $review->branch_id
        );

        return $review->fresh()->load(['user', 'vehicle', 'booking', 'branch', 'adminResponder']);
    }

    public function addAdminResponse(Review $review, string $response, User $actor): Review
    {
        if ($actor->isBranchManager() && !$actor->isAdmin() && $review->branch_id !== $actor->branch_id) {
            throw new \InvalidArgumentException('You can only respond to reviews for your branch.');
        }

        $sanitized = $this->sanitizeComment($response);

        if ($sanitized === null) {
            throw new \InvalidArgumentException('Admin response cannot be empty.');
        }

        $review->update([
            'admin_response' => $sanitized,
            'admin_response_at' => now(),
            'admin_response_by' => $actor->id,
        ]);

        $this->auditLog->log(
            $actor,
            'review_admin_response',
            'review',
            $review->id,
            null,
            ['admin_response' => $sanitized],
            null,
            $review->branch_id
        );

        return $review->fresh()->load(['user', 'vehicle', 'booking', 'branch', 'adminResponder']);
    }

    public function archiveReview(Review $review, User $actor, ?string $reason = null): Review
    {
        return $this->updateStatus($review, Review::STATUS_ARCHIVED, $actor, $reason);
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

    private function validateReviewEligibility(Booking $booking, int $userId): void
    {
        if ($booking->user_id !== $userId) {
            throw new \InvalidArgumentException('You can only review your own bookings.');
        }

        if (in_array($booking->status, [Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED], true)) {
            throw new \InvalidArgumentException('Cancelled or rejected bookings cannot be reviewed.');
        }

        if ($booking->status !== Booking::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Only completed bookings can be reviewed.');
        }

        if (!$booking->picked_up_at) {
            throw new \InvalidArgumentException('This booking has not been picked up yet.');
        }

        if (!$booking->returned_at) {
            throw new \InvalidArgumentException('This booking has not been returned yet.');
        }
    }

    private function validateNoDuplicateReview(Booking $booking, int $userId): void
    {
        if (Review::where('booking_id', $booking->id)->where('user_id', $userId)->exists()) {
            throw new \InvalidArgumentException('This booking has already been reviewed.');
        }
    }

    private function validateRatings(array $data, bool $required = true): void
    {
        $fields = ['overall_rating', 'vehicle_rating', 'cleanliness_rating', 'staff_rating', 'value_rating'];

        foreach ($fields as $field) {
            if (!$required && !array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field] ?? null;

            if ($required && $value === null) {
                throw new \InvalidArgumentException('Please select an overall rating.');
            }

            if ($value !== null && ($value < Review::MIN_RATING || $value > Review::MAX_RATING)) {
                throw new \InvalidArgumentException(
                    ucfirst(str_replace('_', ' ', $field)) . ' must be between ' . Review::MIN_RATING . ' and ' . Review::MAX_RATING . '.'
                );
            }
        }
    }

    private function sanitizeComment(?string $comment): ?string
    {
        if ($comment === null) {
            return null;
        }

        $clean = strip_tags(trim($comment));

        return $clean === '' ? null : $clean;
    }
}
