<?php

namespace Database\Factories;

use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'maintenance_type' => $this->faker->randomElement(['service', 'inspection', 'repair', 'battery', 'brake']),
            'cost' => $this->faker->randomFloat(2, 50, 500),
            'start_date' => now()->subDays(3),
            'end_date' => now()->addDays(2),
            'status' => $this->faker->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled']),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
