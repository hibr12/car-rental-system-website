<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'phone' => fake()->phoneNumber(),
            'role' => 'customer',
            'remember_token' => \Illuminate\Support\Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_COMPANY_ADMIN,
        ]);
    }

    public function branchManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_BRANCH_MANAGER,
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_BRANCH_STAFF,
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_CUSTOMER,
        ]);
    }

    public function fleetManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_COMPANY_ADMIN,
        ]);
    }
}
