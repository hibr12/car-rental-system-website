<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $rating = fake()->numberBetween(1, 5);

        return [
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'booking_id' => Booking::factory(),
            'overall_rating' => $rating,
            'vehicle_rating' => $rating,
            'cleanliness_rating' => $rating,
            'staff_rating' => $rating,
            'value_rating' => $rating,
            'comment' => fake()->paragraph(),
            'status' => Review::STATUS_PUBLISHED,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Review::STATUS_PUBLISHED]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Review::STATUS_HIDDEN]);
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Review::STATUS_FLAGGED]);
    }

    /** @deprecated */
    public function approved(): static
    {
        return $this->published();
    }
}
