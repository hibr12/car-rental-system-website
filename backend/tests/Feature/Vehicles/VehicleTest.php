<?php

namespace Tests\Feature\Vehicles;

use App\Models\Category;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_vehicles(): void
    {
        Vehicle::factory()->count(3)->create();

        $response = $this->getJson('/api/vehicles');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
            ]);

        $response->assertJsonFragment(['success' => true]);
    }

    public function test_anyone_can_view_vehicle_details(): void
    {
        $vehicle = Vehicle::factory()->create();

        $response = $this->getJson("/api/vehicles/{$vehicle->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $vehicle->id,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                ],
            ]);
    }

    public function test_vehicle_creation_works_for_authorized_users(): void
    {
        $user = User::factory()->fleetManager()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/vehicles', [
                'category_id' => $category->id,
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2024,
                'registration_number' => 'NEW-001',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seats' => 5,
                'rental_price_per_day' => 50,
                'images' => [
                    ['image_url' => 'https://example.com/image1.jpg', 'is_primary' => true],
                    ['image_url' => 'https://example.com/image2.jpg', 'is_primary' => false],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'brand' => 'Toyota',
                    'model' => 'Corolla',
                ],
            ]);

        $this->assertDatabaseHas('vehicles', ['registration_number' => 'NEW-001']);
        $this->assertDatabaseCount('vehicle_images', 2);
    }

    public function test_validation_works(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/vehicles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'category_id', 'brand', 'model', 'year',
                'registration_number', 'fuel_type', 'transmission',
                'seats', 'rental_price_per_day',
            ]);
    }

    public function test_filtering_works(): void
    {
        $category = Category::factory()->create(['slug' => 'suv']);
        Vehicle::factory()->count(3)->create(['category_id' => $category->id, 'fuel_type' => 'diesel']);
        Vehicle::factory()->count(2)->create(['fuel_type' => 'petrol']);

        $response = $this->getJson('/api/vehicles?category=suv&fuel_type=diesel');

        $response->assertOk();
        $json = $response->json();
        $this->assertIsArray($json['data']);
        $this->assertCount(3, $json['data']);
    }

    public function test_pagination_works(): void
    {
        Vehicle::factory()->count(25)->create();

        $response = $this->getJson('/api/vehicles?page=1&per_page=10');

        $response->assertOk();
        $json = $response->json();
        $this->assertEquals(25, $json['meta']['total']);
        $this->assertEquals(3, $json['meta']['last_page']);
        $this->assertEquals(1, $json['meta']['current_page']);
    }

    public function test_price_filtering_works(): void
    {
        Vehicle::factory()->create(['rental_price_per_day' => 50]);
        Vehicle::factory()->create(['rental_price_per_day' => 100]);
        Vehicle::factory()->create(['rental_price_per_day' => 200]);

        $response = $this->getJson('/api/vehicles?min_price=60&max_price=150');

        $response->assertOk();
        $json = $response->json();
        $this->assertCount(1, $json['data']);
    }

    public function test_sorting_works(): void
    {
        Vehicle::factory()->create(['rental_price_per_day' => 100]);
        Vehicle::factory()->create(['rental_price_per_day' => 50]);
        Vehicle::factory()->create(['rental_price_per_day' => 200]);

        $response = $this->getJson('/api/vehicles?sort=price_asc');

        $response->assertOk();
        $json = $response->json();
        $this->assertEquals(50, $json['data'][0]['rental_price_per_day']);
        $this->assertEquals(200, $json['data'][2]['rental_price_per_day']);
    }

    public function test_search_works(): void
    {
        Vehicle::factory()->create(['brand' => 'Toyota', 'model' => 'Camry']);
        Vehicle::factory()->create(['brand' => 'Honda', 'model' => 'Civic']);
        Vehicle::factory()->create(['brand' => 'Toyota', 'model' => 'Corolla']);

        $response = $this->getJson('/api/vehicles?search=Toyota');

        $response->assertOk();
        $json = $response->json();
        $this->assertCount(2, $json['data']);
    }

    public function test_vehicle_update_works(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        $vehicle = Vehicle::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/vehicles/{$vehicle->id}", [
                'brand' => 'Updated Brand',
                'rental_price_per_day' => 99,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'brand' => 'Updated Brand',
                    'rental_price_per_day' => 99,
                ],
            ]);
    }

    public function test_vehicle_delete_works(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        $vehicle = Vehicle::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/vehicles/{$vehicle->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }
}
