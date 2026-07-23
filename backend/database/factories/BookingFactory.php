<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $pickupDate = Carbon::tomorrow()->addDays(rand(1, 10));
        $returnDate = (clone $pickupDate)->addDays(rand(1, 7));
        $pricePerDay = fake()->randomFloat(2, 50, 300);
        $numberOfDays = $pickupDate->diffInDays($returnDate);
        $subtotal = $numberOfDays * $pricePerDay;

        return [
            'booking_reference' => 'BK-' . now()->format('Ymd') . '-' . strtoupper(fake()->lexify('??????')),
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'pickup_location' => fake()->randomElement(['Main Branch', 'Airport Branch', 'Downtown Branch']),
            'return_location' => fake()->randomElement(['Main Branch', 'Airport Branch', 'Downtown Branch']),
            'pickup_date' => $pickupDate,
            'return_date' => $returnDate,
            'number_of_days' => $numberOfDays,
            'price_per_day' => $pricePerDay,
            'subtotal' => $subtotal,
            'additional_charges' => 0,
            'discount' => 0,
            'total_price' => $subtotal,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'confirmed']);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'completed']);
    }
}