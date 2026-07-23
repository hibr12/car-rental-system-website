<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@carrental.com',
            'password' => 'password',
        ]);

        User::factory()->fleetManager()->create([
            'name' => 'Fleet Manager',
            'email' => 'fleet@carrental.com',
            'password' => 'password',
        ]);

        User::factory()->staff()->create([
            'name' => 'Staff User',
            'email' => 'staff@carrental.com',
            'password' => 'password',
        ]);

        User::factory()->count(5)->customer()->create();

        $categories = [
            ['name' => 'Economy', 'slug' => 'economy', 'description' => 'Affordable and fuel-efficient vehicles for daily commuting'],
            ['name' => 'Sedan', 'slug' => 'sedan', 'description' => 'Comfortable mid-size cars perfect for business and family travel'],
            ['name' => 'SUV', 'slug' => 'suv', 'description' => 'Spacious sport utility vehicles for all terrain and family adventures'],
            ['name' => 'Luxury', 'slug' => 'luxury', 'description' => 'Premium high-end vehicles for an exceptional driving experience'],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'High-performance vehicles for speed enthusiasts'],
            ['name' => 'Electric', 'slug' => 'electric', 'description' => 'Eco-friendly electric vehicles for sustainable transportation'],
            ['name' => 'Van', 'slug' => 'van', 'description' => 'Spacious vans for group travel and cargo transport'],
        ];

        $createdCategories = [];
        foreach ($categories as $category) {
            $createdCategories[] = Category::create($category);
        }

        $vehicles = [
            ['category_id' => $createdCategories[0]->id, 'brand' => 'Toyota', 'model' => 'Yaris', 'year' => 2024, 'registration_number' => 'ECO-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'White', 'rental_price_per_day' => 35, 'status' => 'available', 'featured' => false, 'location' => 'Main Branch'],
            ['category_id' => $createdCategories[0]->id, 'brand' => 'Honda', 'model' => 'Fit', 'year' => 2023, 'registration_number' => 'ECO-002', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Silver', 'rental_price_per_day' => 38, 'status' => 'available', 'featured' => false, 'location' => 'Airport Branch'],
            ['category_id' => $createdCategories[1]->id, 'brand' => 'Toyota', 'model' => 'Camry', 'year' => 2024, 'registration_number' => 'SED-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Black', 'rental_price_per_day' => 65, 'status' => 'available', 'featured' => true, 'location' => 'Main Branch'],
            ['category_id' => $createdCategories[1]->id, 'brand' => 'Honda', 'model' => 'Accord', 'year' => 2023, 'registration_number' => 'SED-002', 'fuel_type' => 'hybrid', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Blue', 'rental_price_per_day' => 70, 'status' => 'available', 'featured' => true, 'location' => 'Downtown Branch'],
            ['category_id' => $createdCategories[2]->id, 'brand' => 'Ford', 'model' => 'Explorer', 'year' => 2024, 'registration_number' => 'SUV-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 7, 'color' => 'Gray', 'rental_price_per_day' => 95, 'status' => 'available', 'featured' => true, 'location' => 'Main Branch'],
            ['category_id' => $createdCategories[2]->id, 'brand' => 'Hyundai', 'model' => 'Tucson', 'year' => 2024, 'registration_number' => 'SUV-002', 'fuel_type' => 'hybrid', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Red', 'rental_price_per_day' => 80, 'status' => 'available', 'featured' => false, 'location' => 'Airport Branch'],
            ['category_id' => $createdCategories[2]->id, 'brand' => 'Kia', 'model' => 'Sportage', 'year' => 2023, 'registration_number' => 'SUV-003', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'White', 'rental_price_per_day' => 75, 'status' => 'maintenance', 'featured' => false, 'location' => 'Main Branch'],
            ['category_id' => $createdCategories[3]->id, 'brand' => 'BMW', 'model' => '5 Series', 'year' => 2024, 'registration_number' => 'LUX-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Black', 'rental_price_per_day' => 180, 'status' => 'available', 'featured' => true, 'location' => 'Main Branch'],
            ['category_id' => $createdCategories[3]->id, 'brand' => 'Mercedes-Benz', 'model' => 'C-Class', 'year' => 2024, 'registration_number' => 'LUX-002', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Silver', 'rental_price_per_day' => 200, 'status' => 'available', 'featured' => true, 'location' => 'Downtown Branch'],
            ['category_id' => $createdCategories[3]->id, 'brand' => 'Audi', 'model' => 'A4', 'year' => 2023, 'registration_number' => 'LUX-003', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'White', 'rental_price_per_day' => 170, 'status' => 'rented', 'featured' => false, 'location' => 'Airport Branch'],
            ['category_id' => $createdCategories[4]->id, 'brand' => 'Ford', 'model' => 'Mustang', 'year' => 2024, 'registration_number' => 'SPT-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 4, 'color' => 'Red', 'rental_price_per_day' => 150, 'status' => 'available', 'featured' => true, 'location' => 'Main Branch'],
            ['category_id' => $createdCategories[4]->id, 'brand' => 'BMW', 'model' => 'Z4', 'year' => 2023, 'registration_number' => 'SPT-002', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 2, 'color' => 'Blue', 'rental_price_per_day' => 160, 'status' => 'available', 'featured' => false, 'location' => 'Downtown Branch'],
            ['category_id' => $createdCategories[5]->id, 'brand' => 'Tesla', 'model' => 'Model 3', 'year' => 2024, 'registration_number' => 'ELE-001', 'fuel_type' => 'electric', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'White', 'rental_price_per_day' => 90, 'status' => 'available', 'featured' => true, 'location' => 'Main Branch'],
            ['category_id' => $createdCategories[5]->id, 'brand' => 'Tesla', 'model' => 'Model Y', 'year' => 2024, 'registration_number' => 'ELE-002', 'fuel_type' => 'electric', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Black', 'rental_price_per_day' => 110, 'status' => 'available', 'featured' => true, 'location' => 'Airport Branch'],
            ['category_id' => $createdCategories[5]->id, 'brand' => 'Nissan', 'model' => 'Leaf', 'year' => 2023, 'registration_number' => 'ELE-003', 'fuel_type' => 'electric', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Green', 'rental_price_per_day' => 60, 'status' => 'available', 'featured' => false, 'location' => 'Downtown Branch'],
            ['category_id' => $createdCategories[6]->id, 'brand' => 'Ford', 'model' => 'Transit', 'year' => 2024, 'registration_number' => 'VAN-001', 'fuel_type' => 'diesel', 'transmission' => 'automatic', 'seats' => 12, 'color' => 'White', 'rental_price_per_day' => 120, 'status' => 'available', 'featured' => false, 'location' => 'Main Branch'],
            ['category_id' => $createdCategories[6]->id, 'brand' => 'Mercedes-Benz', 'model' => 'Sprinter', 'year' => 2023, 'registration_number' => 'VAN-002', 'fuel_type' => 'diesel', 'transmission' => 'automatic', 'seats' => 8, 'color' => 'Silver', 'rental_price_per_day' => 140, 'status' => 'available', 'featured' => false, 'location' => 'Airport Branch'],
            ['category_id' => $createdCategories[1]->id, 'brand' => 'Nissan', 'model' => 'Altima', 'year' => 2024, 'registration_number' => 'SED-003', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Gray', 'rental_price_per_day' => 55, 'status' => 'available', 'featured' => false, 'location' => 'Shopping Mall Branch'],
            ['category_id' => $createdCategories[0]->id, 'brand' => 'Kia', 'model' => 'Rio', 'year' => 2023, 'registration_number' => 'ECO-003', 'fuel_type' => 'petrol', 'transmission' => 'manual', 'seats' => 5, 'color' => 'Blue', 'rental_price_per_day' => 30, 'status' => 'unavailable', 'featured' => false, 'location' => 'Main Branch'],
            ['category_id' => $createdCategories[2]->id, 'brand' => 'Toyota', 'model' => 'Highlander', 'year' => 2024, 'registration_number' => 'SUV-004', 'fuel_type' => 'hybrid', 'transmission' => 'automatic', 'seats' => 7, 'color' => 'Navy', 'rental_price_per_day' => 105, 'status' => 'available', 'featured' => true, 'location' => 'Main Branch'],
        ];

        foreach ($vehicles as $vehicleData) {
            $vehicle = Vehicle::create($vehicleData);

            $imageCount = fake()->numberBetween(2, 4);
            for ($i = 0; $i < $imageCount; $i++) {
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_url' => "https://picsum.photos/seed/{$vehicle->registration_number}-{$i}/800/600",
                    'is_primary' => $i === 0,
                ]);
            }
        }
    }
}
