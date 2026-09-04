<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $branchManager;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->branch = Branch::factory()->create(['name' => 'Test Branch']);
        $this->branchManager = User::factory()->branchManager()->create([
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_admin_can_create_branch(): void
    {
        $token = $this->admin->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/branches', [
                'name' => 'New Branch',
                'address' => '123 Main St',
                'city' => 'Addis Ababa',
                'phone' => '+251-11-123-4567',
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Branch created successfully.',
            ]);

        $this->assertDatabaseHas('branches', ['name' => 'New Branch']);
    }

    public function test_admin_can_update_branch(): void
    {
        $token = $this->admin->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/branches/' . $this->branch->id, [
                'name' => 'Updated Branch',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Branch updated successfully.',
            ]);
    }

    public function test_admin_can_delete_branch(): void
    {
        $token = $this->admin->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/branches/' . $this->branch->id);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Branch deleted successfully.',
            ]);

        $this->assertDatabaseMissing('branches', ['id' => $this->branch->id]);
    }

    public function test_branch_manager_can_view_own_branch(): void
    {
        $token = $this->branchManager->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/branches/' . $this->branch->id);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_branch_manager_cannot_view_other_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $token = $this->branchManager->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/branches/' . $otherBranch->id);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_manage_branches(): void
    {
        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/branches', [
                'name' => 'Unauthorized Branch',
                'address' => '123 Main St',
                'city' => 'Addis Ababa',
            ]);

        $response->assertStatus(403);
    }

    public function test_branch_manager_can_view_dashboard(): void
    {
        $token = $this->branchManager->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/branch-manager/dashboard');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'today_bookings',
                    'active_rentals',
                    'pending_approvals',
                    'available_vehicles',
                    'maintenance_vehicles',
                    'today_revenue',
                    'monthly_revenue',
                    'recent_bookings',
                    'staff',
                ],
            ]);
    }
}
