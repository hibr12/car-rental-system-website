<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CustomNotification;
use App\Models\Vehicle;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function createBooking(array $data, int $userId): Booking
    {
        return DB::transaction(function () use ($data, $userId) {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);

            if (!in_array($vehicle->status, ['available', 'reserved'])) {
                throw new \InvalidArgumentException('Vehicle is not available for booking.');
            }

            $pickupDate = Carbon::parse($data['pickup_date']);
            $returnDate = Carbon::parse($data['return_date']);

            if ($returnDate <= $pickupDate) {
                throw new \InvalidArgumentException('Return date must be after pickup date.');
            }

            if ($this->hasOverlap($vehicle->id, $pickupDate, $returnDate)) {
                throw new \InvalidArgumentException('Vehicle is already booked for the selected dates.');
            }

            $numberOfDays = max(1, $pickupDate->diffInDays($returnDate));
            $pricePerDay = (float) $vehicle->rental_price_per_day;
            $subtotal = $numberOfDays * $pricePerDay;
            $additionalCharges = (float) ($data['additional_charges'] ?? 0);
            $discount = (float) ($data['discount'] ?? 0);
            $totalPrice = $subtotal + $additionalCharges - $discount;

            $booking = Booking::create([
                'booking_reference' => $this->generateReference(),
                'user_id' => $userId,
                'vehicle_id' => $vehicle->id,
                'branch_id' => $vehicle->branch_id,
                'pickup_location' => $data['pickup_location'],
                'return_location' => $data['return_location'],
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
                'number_of_days' => $numberOfDays,
                'price_per_day' => $pricePerDay,
                'subtotal' => $subtotal,
                'additional_charges' => $additionalCharges,
                'discount' => $discount,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
            ]);

            $booking->load('vehicle', 'user');
            $booking->user->notify(new BookingCreated($booking));

            $this->createNotification(
                $booking->user_id,
                'reservation_created',
                'New Reservation Created',
                "Your reservation for {$vehicle->brand} {$vehicle->model} has been created.",
                Booking::class,
                $booking->id
            );

            $this->notifyBranchStaff($booking->branch_id, 'reservation_created', 'New Reservation', "New reservation #{$booking->booking_reference} requires processing.", Booking::class, $booking->id);

            return $booking;
        });
    }

    public function confirmBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== 'pending') {
                throw new \InvalidArgumentException('Only pending bookings can be confirmed.');
            }

            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'pending',
            ]);

            $booking->vehicle->update(['status' => 'reserved']);

            $this->createNotification(
                $booking->user_id,
                'booking_confirmed',
                'Reservation Confirmed',
                "Your reservation #{$booking->booking_reference} has been confirmed.",
                Booking::class,
                $booking->id
            );

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function rejectBooking(Booking $booking, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $reason) {
            if (!in_array($booking->status, ['pending'])) {
                throw new \InvalidArgumentException('Only pending bookings can be rejected.');
            }

            $booking->update([
                'status' => 'rejected',
                'notes' => $reason ? ($booking->notes ? $booking->notes . "\nRejection: " . $reason : 'Rejection: ' . $reason) : $booking->notes,
            ]);

            $this->createNotification(
                $booking->user_id,
                'booking_cancelled',
                'Reservation Rejected',
                "Your reservation #{$booking->booking_reference} has been rejected." . ($reason ? " Reason: {$reason}" : ''),
                Booking::class,
                $booking->id
            );

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function cancelBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if (!in_array($booking->status, ['pending', 'confirmed'])) {
                throw new \InvalidArgumentException('Only pending or confirmed bookings can be cancelled.');
            }

            $booking->update(['status' => 'cancelled']);

            if ($booking->vehicle->status === 'reserved') {
                $booking->vehicle->update(['status' => 'available']);
            }

            $this->createNotification(
                $booking->user_id,
                'booking_cancelled',
                'Reservation Cancelled',
                "Your reservation #{$booking->booking_reference} has been cancelled.",
                Booking::class,
                $booking->id
            );

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function markAsPickedUp(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== 'confirmed') {
                throw new \InvalidArgumentException('Only confirmed bookings can be marked as picked up.');
            }

            $booking->update(['status' => 'active']);
            $booking->vehicle->update(['status' => 'rented']);

            $this->createNotification(
                $booking->user_id,
                'pickup_completed',
                'Vehicle Picked Up',
                "You have picked up {$booking->vehicle->brand} {$booking->vehicle->model} for reservation #{$booking->booking_reference}.",
                Booking::class,
                $booking->id
            );

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function markAsReturned(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== 'active') {
                throw new \InvalidArgumentException('Only active rentals can be returned.');
            }

            $booking->update([
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            $booking->vehicle->update(['status' => 'available']);

            $this->createNotification(
                $booking->user_id,
                'booking_completed',
                'Rental Completed',
                "Your rental for {$booking->vehicle->brand} {$booking->vehicle->model} has been completed. Thank you!",
                Booking::class,
                $booking->id
            );

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function approveBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== 'pending') {
                throw new \InvalidArgumentException('Only pending bookings can be approved.');
            }

            $booking->update(['status' => 'confirmed']);

            $this->createNotification(
                $booking->user_id,
                'booking_confirmed',
                'Reservation Approved',
                "Your reservation #{$booking->booking_reference} has been approved.",
                Booking::class,
                $booking->id
            );

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function hasOverlap(int $vehicleId, Carbon $pickupDate, Carbon $returnDate, ?int $excludeBookingId = null): bool
    {
        $query = Booking::overlapping($vehicleId, $pickupDate, $returnDate);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    private function generateReference(): string
    {
        $prefix = 'BK-' . now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return $prefix . '-' . $random;
    }

    private function createNotification(int $userId, string $type, string $title, string $message, ?string $relatedType = null, ?int $relatedId = null): void
    {
        CustomNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'notifiable_type' => \App\Models\User::class,
            'notifiable_id' => $userId,
            'data' => json_encode([]),
        ]);
    }

    private function notifyBranchStaff(?int $branchId, string $type, string $title, string $message, ?string $relatedType = null, ?int $relatedId = null): void
    {
        if (!$branchId) {
            return;
        }

        $staffUsers = \App\Models\User::where('branch_id', $branchId)
            ->whereIn('role', ['staff', 'branch_manager'])
            ->get();

        foreach ($staffUsers as $staff) {
            $this->createNotification($staff->id, $type, $title, $message, $relatedType, $relatedId);
        }
    }
}
