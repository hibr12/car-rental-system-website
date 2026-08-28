<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Bole Branch', 'Kazanchis Branch', 'CMC Branch']),
            'code' => strtoupper(fake()->unique()->bothify('BR-###')),
            'address' => fake()->streetAddress(),
            'city' => 'Addis Ababa',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'status' => 'active',
        ];
    }
}
