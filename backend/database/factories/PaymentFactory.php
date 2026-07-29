<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 500),
            'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'card', 'online_payment']),
            'transaction_reference' => 'TXN-' . now()->format('Ymd') . '-' . strtoupper($this->faker->lexify('????????')),
            'status' => 'paid',
            'paid_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'paid', 'paid_at' => now()]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending', 'paid_at' => null]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'failed', 'paid_at' => null]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'refunded']);
    }
}
