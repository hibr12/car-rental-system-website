<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
