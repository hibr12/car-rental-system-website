<?php

namespace Tests\Feature\Authorization;

use App\Models\Category;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_create_vehicle(): void
    {
        $user = User::factory()->customer()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/vehicles', [
                'category_id' => $category->id,
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2024,
                'registration_number' => 'TEST-001',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seats' => 5,
                'rental_price_per_day' => 50,
            ]);

        $response->assertStatus(403);
    }

    public function test_fleet_manager_can_create_vehicle(): void
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
                'registration_number' => 'TEST-001',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seats' => 5,
                'rental_price_per_day' => 50,
            ]);

        $response->assertStatus(201);
    }

    public function test_admin_can_create_vehicle(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth-token')->plainTextToken;
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/vehicles', [
                'category_id' => $category->id,
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2024,
                'registration_number' => 'TEST-002',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seats' => 5,
                'rental_price_per_day' => 50,
            ]);

        $response->assertStatus(201);
    }

    public function test_unauthorized_user_cannot_create_vehicle(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/vehicles', [
            'category_id' => $category->id,
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'registration_number' => 'TEST-003',
            'fuel_type' => 'petrol',
            'transmission' => 'automatic',
            'seats' => 5,
            'rental_price_per_day' => 50,
        ]);

        $response->assertStatus(401);
    }

    public function test_customer_cannot_create_category(): void
    {
        $user = User::factory()->customer()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/categories', [
                'name' => 'New Category',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_category(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/categories', [
                'name' => 'New Category',
            ]);

        $response->assertStatus(201);
    }

    public function test_fleet_manager_cannot_delete_vehicle(): void
    {
        $fleetManager = User::factory()->fleetManager()->create();
        $fleetToken = $fleetManager->createToken('auth-token')->plainTextToken;
        $vehicle = Vehicle::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $fleetToken)
            ->deleteJson("/api/vehicles/{$vehicle->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_vehicle(): void
    {
        $admin = User::factory()->admin()->create();
        $adminToken = $admin->createToken('auth-token')->plainTextToken;
        $vehicle = Vehicle::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->deleteJson("/api/vehicles/{$vehicle->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }
}
