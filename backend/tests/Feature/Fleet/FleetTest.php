<?php

namespace Tests\Feature\Fleet;

use App\Models\Branch;
use App\Models\Category;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetTest extends TestCase
{
    use RefreshDatabase;

    public function test_fleet_manager_can_access_dashboard(): void
    {
        $fleet = User::factory()->fleetManager()->create();
        $token = $fleet->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/fleet/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_vehicles',
                    'available',
                    'maintenance',
                    'utilization_pct',
                ],
            ]);
    }

    public function test_fleet_manager_sees_all_branch_vehicles_in_report(): void
    {
        $branchA = Branch::factory()->create(['code' => 'BOLE']);
        $branchB = Branch::factory()->create(['code' => 'CMC']);
        $category = Category::factory()->create();

        Vehicle::factory()->create(['branch_id' => $branchA->id, 'category_id' => $category->id, 'status' => 'available']);
        Vehicle::factory()->create(['branch_id' => $branchB->id, 'category_id' => $category->id, 'status' => 'rented']);

        $fleet = User::factory()->fleetManager()->create();
        $token = $fleet->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/reports/fleet');

        $response->assertOk()->assertJsonPath('data.total', 2);
    }

    public function test_branch_manager_sees_only_own_branch_in_fleet_report(): void
    {
        $branchA = Branch::factory()->create(['code' => 'BOLE']);
        $branchB = Branch::factory()->create(['code' => 'CMC']);
        $category = Category::factory()->create();

        Vehicle::factory()->create(['branch_id' => $branchA->id, 'category_id' => $category->id]);
        Vehicle::factory()->create(['branch_id' => $branchB->id, 'category_id' => $category->id]);

        $manager = User::factory()->branchManager()->create(['branch_id' => $branchA->id]);
        $token = $manager->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/reports/fleet')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    public function test_customer_cannot_access_fleet_dashboard(): void
    {
        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/fleet/dashboard')
            ->assertStatus(403);
    }

    public function test_fleet_manager_can_create_vehicle(): void
    {
        $fleet = User::factory()->fleetManager()->create();
        $category = Category::factory()->create();
        $branch = Branch::factory()->create();
        $token = $fleet->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/vehicles', [
                'category_id' => $category->id,
                'branch_id' => $branch->id,
                'brand' => 'Toyota',
                'model' => 'Camry',
                'year' => 2024,
                'registration_number' => 'FLT-001',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seats' => 5,
                'rental_price_per_day' => 100,
            ])
            ->assertStatus(201);
    }
}
