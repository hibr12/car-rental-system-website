<?php

namespace App\Services;

use App\Events\BookingCancelled;
use App\Events\BookingCompleted;
use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Events\BookingPickedUp;
use App\Events\BookingRejected;
use App\Models\Booking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function createBooking(array $data, int $userId): Booking
    {
        return DB::transaction(function () use ($data, $userId) {
            $vehicle = $this->findVehicleOrFail($data['vehicle_id']);

            $this->validateVehicle($vehicle);

            $pickupDate = Carbon::parse($data['pickup_date']);
            $returnDate = Carbon::parse($data['return_date']);

            $this->validateDates($pickupDate, $returnDate);
            $this->validateNoOverlap($vehicle->id, $pickupDate, $returnDate);

            $numberOfDays = $this->calculateNumberOfDays($pickupDate, $returnDate);
            $pricePerDay = $this->getPricePerDay($vehicle);
            $subtotal = $this->calculateSubtotal($numberOfDays, $pricePerDay);
            $additionalCharges = $this->resolveAdditionalCharges($data);
            $discount = $this->resolveDiscount($data);
            $totalPrice = $this->calculateTotalPrice($subtotal, $additionalCharges, $discount);

            $booking = Booking::create([
                'booking_reference' => $this->generateReference(),
                'user_id' => $userId,
                'vehicle_id' => $vehicle->id,
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
                'status' => Booking::STATUS_PENDING,
                'payment_status' => Booking::PAYMENT_STATUS_UNPAID,
                'notes' => $data['notes'] ?? null,
            ]);

            $booking->load('vehicle', 'user');

            event(new BookingCreated($booking));

            return $booking;
        });
    }

    public function confirmBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== Booking::STATUS_PENDING) {
                throw new \InvalidArgumentException('Only pending bookings can be confirmed.');
            }

            $booking->update([
                'status' => Booking::STATUS_CONFIRMED,
                'payment_status' => Booking::PAYMENT_STATUS_PENDING,
            ]);

            $booking->vehicle->update(['status' => 'reserved']);

            event(new BookingConfirmed($booking));

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function rejectBooking(Booking $booking, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $reason) {
            if ($booking->status !== Booking::STATUS_PENDING) {
                throw new \InvalidArgumentException('Only pending bookings can be rejected.');
            }

            $notes = $booking->notes;
            if ($reason) {
                $notes = $notes ? $notes . "\nRejection: " . $reason : 'Rejection: ' . $reason;
            }

            $booking->update([
                'status' => Booking::STATUS_REJECTED,
                'notes' => $notes,
            ]);

            event(new BookingRejected($booking, $reason));

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function cancelBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if (!in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])) {
                throw new \InvalidArgumentException('Only pending or confirmed bookings can be cancelled.');
            }

            $booking->update(['status' => Booking::STATUS_CANCELLED]);

            if ($booking->vehicle->status === 'reserved') {
                $booking->vehicle->update(['status' => 'available']);
            }

            event(new BookingCancelled($booking));

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function markAsPickedUp(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== Booking::STATUS_CONFIRMED) {
                throw new \InvalidArgumentException('Only confirmed bookings can be marked as picked up.');
            }

            $booking->update(['status' => Booking::STATUS_ACTIVE]);
            $booking->vehicle->update(['status' => 'rented']);

            event(new BookingPickedUp($booking));

            return $booking->fresh()->load('vehicle', 'user');
        });
    }

    public function markAsReturned(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== Booking::STATUS_ACTIVE) {
                throw new \InvalidArgumentException('Only active rentals can be returned.');
            }

            $booking->update([
                'status' => Booking::STATUS_COMPLETED,
                'payment_status' => Booking::PAYMENT_STATUS_PAID,
            ]);

            $booking->vehicle->update(['status' => 'available']);

            event(new BookingCompleted($booking));

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

    private function findVehicleOrFail(int $vehicleId): Vehicle
    {
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            throw new \InvalidArgumentException('The selected vehicle does not exist.');
        }

        return $vehicle;
    }

    private function validateVehicle(Vehicle $vehicle): void
    {
        if ($vehicle->status === 'maintenance') {
            throw new \InvalidArgumentException('Vehicle is currently under maintenance and cannot be booked.');
        }

        if ($vehicle->status === 'unavailable') {
            throw new \InvalidArgumentException('Vehicle is marked as unavailable and cannot be booked.');
        }

        if ($vehicle->status !== 'available') {
            throw new \InvalidArgumentException('Vehicle is not available for booking.');
        }
    }

    private function validateDates(Carbon $pickupDate, Carbon $returnDate): void
    {
        if ($returnDate <= $pickupDate) {
            throw new \InvalidArgumentException('Return date must be after pickup date.');
        }
    }

    private function validateNoOverlap(int $vehicleId, Carbon $pickupDate, Carbon $returnDate, ?int $excludeBookingId = null): void
    {
        if ($this->hasOverlap($vehicleId, $pickupDate, $returnDate, $excludeBookingId)) {
            throw new \InvalidArgumentException('Vehicle is already booked for the selected dates.');
        }
    }

    private function calculateNumberOfDays(Carbon $pickupDate, Carbon $returnDate): int
    {
        return max(1, $pickupDate->diffInDays($returnDate));
    }

    private function getPricePerDay(Vehicle $vehicle): float
    {
        return (float) $vehicle->rental_price_per_day;
    }

    private function calculateSubtotal(int $numberOfDays, float $pricePerDay): float
    {
        return $numberOfDays * $pricePerDay;
    }

    private function resolveAdditionalCharges(array $data): float
    {
        return (float) ($data['additional_charges'] ?? 0);
    }

    private function resolveDiscount(array $data): float
    {
        return (float) ($data['discount'] ?? 0);
    }

    private function calculateTotalPrice(float $subtotal, float $additionalCharges, float $discount): float
    {
        return $subtotal + $additionalCharges - $discount;
    }

    private function generateReference(): string
    {
        $prefix = 'BK-' . now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return $prefix . '-' . $random;
    }
}
