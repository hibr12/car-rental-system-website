<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BranchAccessTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private User $branchManager;
    private User $staff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->branchManager = User::factory()->branchManager()->create(['branch_id' => $this->branch->id]);
        $this->staff = User::factory()->staff()->create(['branch_id' => $this->branch->id]);
        $this->admin = User::factory()->admin()->create();
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

    public function test_staff_cannot_view_branch_details(): void
    {
        $token = $this->staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/branches/' . $this->branch->id);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_branches(): void
    {
        $token = $this->admin->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/branches');

        $response->assertOk();
    }

    public function test_admin_branch_list_keeps_operational_aggregate_fields(): void
    {
        $token = $this->admin->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/branches')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'pending_transfers_count',
                    'maintenance_count',
                    'monthly_revenue',
                    'total_revenue',
                ]],
            ]);
    }

    public function test_branch_dashboard_returns_aggregates_from_compact_queries(): void
    {
        $token = $this->admin->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/branches/' . $this->branch->id . '/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_vehicles', 0)
            ->assertJsonPath('data.todays_bookings', 0)
            ->assertJsonPath('data.total_revenue', 0);
    }

    public function test_public_branch_directory_omits_management_metrics_and_inactive_branches(): void
    {
        $this->branch->update(['status' => 'active']);
        Branch::factory()->create(['status' => 'inactive']);

        $this->getJson('/api/branches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->branch->id)
            ->assertJsonMissingPath('data.0.total_revenue')
            ->assertJsonMissingPath('data.0.bookings_count');
    }

    public function test_branch_mutation_invalidates_public_directory_cache(): void
    {
        $this->branch->update(['status' => 'active']);

        $this->getJson('/api/branches')
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->branch->id);

        $token = $this->admin->createToken('auth-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/branches/' . $this->branch->id . '/deactivate')
            ->assertOk();

        $this->assertFalse(Cache::has('branches:directory:active'));
    }

    public function test_customer_cannot_manage_branches(): void
    {
        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/branches', [
                'name' => 'New Branch',
                'address' => '123 Main St',
                'city' => 'Addis Ababa',
            ]);

        $response->assertStatus(403);
    }

    public function test_staff_cannot_manage_branches(): void
    {
        $token = $this->staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/branches', [
                'name' => 'New Branch',
                'address' => '123 Main St',
                'city' => 'Addis Ababa',
            ]);

        $response->assertStatus(403);
    }

    public function test_branch_manager_cannot_create_branches(): void
    {
        $token = $this->branchManager->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/branches', [
                'name' => 'New Branch',
                'address' => '123 Main St',
                'city' => 'Addis Ababa',
            ]);

        $response->assertStatus(403);
    }
}
