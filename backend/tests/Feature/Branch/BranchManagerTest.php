<?php

namespace Tests\Feature\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagerTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;
    private Branch $branchB;
    private User $managerA;
    private User $managerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::factory()->create(['code' => 'CMC']);
        $this->branchB = Branch::factory()->create(['code' => 'BOLE']);
        $this->managerA = User::factory()->branchManager()->create(['branch_id' => $this->branchA->id]);
        $this->managerB = User::factory()->branchManager()->create(['branch_id' => $this->branchB->id]);
    }

    public function test_branch_manager_can_access_dashboard(): void
    {
        $token = $this->managerA->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/branch/dashboard')
            ->assertOk()
            ->assertJsonPath('data.branch.id', $this->branchA->id)
            ->assertJsonStructure([
                'data' => [
                    'todays_pickups',
                    'todays_returns',
                    'pending_approvals',
                    'active_rentals',
                    'available_vehicles',
                ],
            ]);
    }

    public function test_branch_manager_cannot_access_fleet_dashboard(): void
    {
        $token = $this->managerA->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/fleet/dashboard')
            ->assertStatus(403);
    }

    public function test_branch_manager_sees_only_own_branch_bookings(): void
    {
        $category = Category::factory()->create();
        $vehicleA = Vehicle::factory()->create(['branch_id' => $this->branchA->id, 'category_id' => $category->id]);
        $vehicleB = Vehicle::factory()->create(['branch_id' => $this->branchB->id, 'category_id' => $category->id]);

        Booking::factory()->create(['branch_id' => $this->branchA->id, 'vehicle_id' => $vehicleA->id]);
        Booking::factory()->create(['branch_id' => $this->branchB->id, 'vehicle_id' => $vehicleB->id]);

        $token = $this->managerA->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/bookings')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_branch_manager_cannot_approve_other_branch_booking(): void
    {
        $category = Category::factory()->create();
        $vehicleB = Vehicle::factory()->create(['branch_id' => $this->branchB->id, 'category_id' => $category->id]);
        $booking = Booking::factory()->create([
            'branch_id' => $this->branchB->id,
            'vehicle_id' => $vehicleB->id,
            'status' => Booking::STATUS_PENDING_BRANCH_APPROVAL,
            'branch_approval_status' => Booking::APPROVAL_PENDING,
        ]);

        $token = $this->managerA->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/admin/bookings/{$booking->id}/confirm")
            ->assertStatus(403);
    }

    public function test_branch_manager_can_create_maintenance_request(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'branch_id' => $this->branchA->id,
            'category_id' => $category->id,
        ]);

        $token = $this->managerA->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/maintenance-requests', [
                'vehicle_id' => $vehicle->id,
                'title' => 'Brake noise',
                'description' => 'Abnormal brake sound during return inspection.',
                'priority' => 'high',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('maintenance_requests', [
            'vehicle_id' => $vehicle->id,
            'branch_id' => $this->branchA->id,
            'status' => MaintenanceRequest::STATUS_REQUESTED,
        ]);
    }

    public function test_branch_manager_cannot_request_maintenance_for_other_branch_vehicle(): void
    {
        $category = Category::factory()->create();
        $vehicleB = Vehicle::factory()->create([
            'branch_id' => $this->branchB->id,
            'category_id' => $category->id,
        ]);

        $token = $this->managerA->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/maintenance-requests', [
                'vehicle_id' => $vehicleB->id,
                'title' => 'Test',
                'description' => 'Should fail',
            ])
            ->assertStatus(422);
    }

    public function test_branch_manager_sees_branch_customers_only(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create(['branch_id' => $this->branchA->id, 'category_id' => $category->id]);

        Booking::factory()->create([
            'user_id' => $customer->id,
            'branch_id' => $this->branchA->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $token = $this->managerA->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/branch/customers')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_fleet_manager_can_approve_maintenance_request(): void
    {
        $fleet = User::factory()->fleetManager()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create(['branch_id' => $this->branchA->id, 'category_id' => $category->id]);

        $request = MaintenanceRequest::create([
            'vehicle_id' => $vehicle->id,
            'branch_id' => $this->branchA->id,
            'requested_by' => $this->managerA->id,
            'title' => 'Oil change',
            'description' => 'Scheduled service',
            'priority' => 'medium',
            'status' => MaintenanceRequest::STATUS_REQUESTED,
        ]);

        $token = $fleet->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/maintenance-requests/{$request->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', MaintenanceRequest::STATUS_APPROVED);
    }

    public function test_admin_can_create_branch_with_manager(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_COMPANY_ADMIN]);
        $token = $admin->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/branches', [
                'name' => 'Test Branch',
                'code' => 'TEST',
                'address' => 'Test Address',
                'city' => 'Addis Ababa',
                'create_manager' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.manager.email', 'test.manager@apexrentals.com');

        $this->assertDatabaseHas('users', [
            'email' => 'test.manager@apexrentals.com',
            'role' => User::ROLE_BRANCH_MANAGER,
        ]);
    }
}
