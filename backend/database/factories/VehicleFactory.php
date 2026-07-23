<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $brands = ['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes-Benz', 'Audi', 'Nissan', 'Hyundai', 'Kia', 'Tesla'];
        $models = ['Corolla', 'Civic', 'Mustang', '3 Series', 'C-Class', 'A4', 'Altima', 'Tucson', 'Sportage', 'Model 3'];
        $fuelTypes = ['petrol', 'diesel', 'electric', 'hybrid'];
        $transmissions = ['automatic', 'manual'];
        $statuses = ['available', 'rented', 'reserved', 'maintenance', 'unavailable'];
        $locations = ['Main Branch', 'Airport Branch', 'Downtown Branch', 'Shopping Mall Branch'];

        $brand = fake()->randomElement($brands);

        return [
            'category_id' => Category::factory(),
            'brand' => $brand,
            'model' => fake()->randomElement($models),
            'year' => fake()->numberBetween(2018, 2026),
            'registration_number' => strtoupper(fake()->bothify('??-####')),
            'vin_number' => strtoupper(fake()->bothify('?????????????????')),
            'description' => fake()->paragraph(),
            'fuel_type' => fake()->randomElement($fuelTypes),
            'transmission' => fake()->randomElement($transmissions),
            'seats' => fake()->randomElement([2, 4, 5, 7]),
            'color' => fake()->safeColorName(),
            'mileage' => fake()->numberBetween(0, 100000),
            'purchase_price' => fake()->randomFloat(2, 15000, 80000),
            'rental_price_per_day' => fake()->randomFloat(2, 30, 300),
            'status' => fake()->randomElement($statuses),
            'featured' => fake()->boolean(30),
            'location' => fake()->randomElement($locations),
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }
}
