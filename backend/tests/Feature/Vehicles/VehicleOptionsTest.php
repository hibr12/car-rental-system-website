<?php

namespace Tests\Feature\Vehicles;

use App\Models\Branch;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_options_requires_an_authenticated_management_role(): void
    {
        $this->getJson('/api/vehicles/options')->assertUnauthorized();

        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/vehicles/options')
            ->assertForbidden();
    }

    public function test_branch_scoped_user_only_sees_own_active_branch(): void
    {
        $ownBranch = Branch::factory()->create(['status' => 'active']);
        $otherBranch = Branch::factory()->create(['status' => 'active']);
        $manager = User::factory()->branchManager()->create(['branch_id' => $ownBranch->id]);
        $ownVehicle = Vehicle::factory()->available()->create(['branch_id' => $ownBranch->id]);
        Vehicle::factory()->available()->create(['branch_id' => $otherBranch->id]);

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/vehicles/options')
            ->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownVehicle->id);

        $this->actingAs($manager, 'sanctum')
            ->getJson('/api/vehicles/options?branch_id=' . $otherBranch->id)
            ->assertForbidden();
    }

    public function test_non_admin_options_exclude_inactive_branches(): void
    {
        $activeBranch = Branch::factory()->create(['status' => 'active']);
        $inactiveBranch = Branch::factory()->create(['status' => 'inactive']);
        $fleetManager = User::factory()->fleetManager()->create();
        $activeVehicle = Vehicle::factory()->available()->create(['branch_id' => $activeBranch->id]);
        Vehicle::factory()->available()->create(['branch_id' => $inactiveBranch->id]);

        $response = $this->actingAs($fleetManager, 'sanctum')
            ->getJson('/api/vehicles/options')
            ->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeVehicle->id);
    }

    public function test_branch_options_exclude_vehicles_with_an_active_outgoing_transfer(): void
    {
        $source = Branch::factory()->create(['status' => 'active']);
        $destination = Branch::factory()->create(['status' => 'active']);
        $manager = User::factory()->branchManager()->create(['branch_id' => $source->id]);
        $eligible = Vehicle::factory()->available()->create(['branch_id' => $source->id]);
        $transferring = Vehicle::factory()->available()->create(['branch_id' => $source->id]);

        VehicleTransfer::create([
            'vehicle_id' => $transferring->id,
            'from_branch_id' => $source->id,
            'to_branch_id' => $destination->id,
            'requested_by' => $manager->id,
            'transfer_date' => now()->addDay()->toDateString(),
            'status' => VehicleTransfer::STATUS_PENDING,
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/vehicles/options')
            ->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $eligible->id);
    }

    public function test_admin_can_filter_any_branch_and_receives_only_compact_fields(): void
    {
        $branch = Branch::factory()->create(['status' => 'inactive']);
        $otherBranch = Branch::factory()->create(['status' => 'active']);
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $branch->id,
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'registration_number' => 'OPT-001',
        ]);
        Vehicle::factory()->available()->create(['branch_id' => $otherBranch->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/vehicles/options?branch_id=' . $branch->id)
            ->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertExactJson([
                'success' => true,
                'message' => 'Vehicle options retrieved successfully',
                'data' => [[
                    'id' => $vehicle->id,
                    'brand' => 'Toyota',
                    'model' => 'Corolla',
                    'registration_number' => 'OPT-001',
                    'branch_id' => $branch->id,
                    'status' => Vehicle::STATUS_AVAILABLE,
                ]],
            ]);
    }
}
