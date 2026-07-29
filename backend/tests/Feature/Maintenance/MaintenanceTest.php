<?php

namespace Tests\Feature\Maintenance;

use App\Models\Category;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $fleetManager;
    private User $staff;
    private User $customer;
    private string $adminToken;
    private string $fleetToken;
    private string $staffToken;
    private string $customerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->fleetManager = User::factory()->fleetManager()->create();
        $this->staff = User::factory()->staff()->create();
        $this->customer = User::factory()->customer()->create();

        $this->adminToken = $this->admin->createToken('auth-token')->plainTextToken;
        $this->fleetToken = $this->fleetManager->createToken('auth-token')->plainTextToken;
        $this->staffToken = $this->staff->createToken('auth-token')->plainTextToken;
        $this->customerToken = $this->customer->createToken('auth-token')->plainTextToken;
    }

    public function test_admin_can_list_maintenance_records(): void
    {
        Maintenance::factory()->count(3)->create(['created_by' => $this->admin->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/maintenance');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
            ]);
    }

    public function test_fleet_manager_can_list_maintenance_records(): void
    {
        Maintenance::factory()->count(2)->create(['created_by' => $this->fleetManager->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->fleetToken)
            ->getJson('/api/maintenance');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_cannot_list_maintenance_records(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->customerToken)
            ->getJson('/api/maintenance');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_maintenance_record(): void
    {
        $maintenance = Maintenance::factory()->create(['created_by' => $this->admin->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/maintenance/' . $maintenance->id);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['id' => $maintenance->id],
            ]);
    }

    public function test_admin_can_create_maintenance_record(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/maintenance', [
                'vehicle_id' => $vehicle->id,
                'title' => 'Engine service',
                'description' => 'Full engine overhaul',
                'maintenance_type' => 'service',
                'cost' => 500,
                'start_date' => now()->toDateTimeString(),
                'status' => 'scheduled',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('maintenances', ['title' => 'Engine service']);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'maintenance']);
    }

    public function test_fleet_manager_can_create_maintenance_record(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->fleetToken)
            ->postJson('/api/maintenance', [
                'vehicle_id' => $vehicle->id,
                'title' => 'Brake check',
                'description' => 'Inspect brakes',
                'maintenance_type' => 'brake',
                'cost' => 150,
                'start_date' => now()->toDateTimeString(),
                'status' => 'scheduled',
            ]);

        $response->assertStatus(201);
    }

    public function test_customer_cannot_create_maintenance_record(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->customerToken)
            ->postJson('/api/maintenance', [
                'vehicle_id' => $vehicle->id,
                'title' => 'Engine service',
                'maintenance_type' => 'service',
                'cost' => 500,
                'start_date' => now()->toDateTimeString(),
                'status' => 'scheduled',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_maintenance_record(): void
    {
        $maintenance = Maintenance::factory()->create([
            'created_by' => $this->admin->id,
            'status' => 'scheduled',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/maintenance/' . $maintenance->id, [
                'status' => 'in_progress',
                'cost' => 250,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('maintenances', ['id' => $maintenance->id, 'status' => 'in_progress']);
    }

    public function test_completing_maintenance_releases_vehicle(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'maintenance']);
        $maintenance = Maintenance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $this->admin->id,
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/maintenance/' . $maintenance->id, [
                'status' => 'completed',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('maintenances', ['id' => $maintenance->id, 'status' => 'completed']);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'available']);
    }

    public function test_admin_can_delete_maintenance_record(): void
    {
        $maintenance = Maintenance::factory()->create(['created_by' => $this->admin->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson('/api/maintenance/' . $maintenance->id);

        $response->assertOk();
        $this->assertDatabaseMissing('maintenances', ['id' => $maintenance->id]);
    }

    public function test_unauthenticated_user_cannot_access_maintenance(): void
    {
        $response = $this->getJson('/api/maintenance');
        $response->assertStatus(401);
    }

    public function test_maintenance_validation_works(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/maintenance', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vehicle_id', 'title', 'maintenance_type', 'start_date']);
    }

    public function test_maintenance_filter_by_status(): void
    {
        Maintenance::factory()->count(2)->create(['status' => 'completed', 'created_by' => $this->admin->id]);
        Maintenance::factory()->create(['status' => 'in_progress', 'created_by' => $this->admin->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/maintenance?status=completed');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_maintenance_filter_by_vehicle(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create(['category_id' => $category->id]);
        Maintenance::factory()->count(2)->create(['vehicle_id' => $vehicle->id, 'created_by' => $this->admin->id]);
        Maintenance::factory()->create(['created_by' => $this->admin->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/maintenance?vehicle_id=' . $vehicle->id);

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
