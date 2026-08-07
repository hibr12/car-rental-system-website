<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'subject' => $this->faker->sentence(3),
            'message' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'read', 'replied']),
            'replied_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
