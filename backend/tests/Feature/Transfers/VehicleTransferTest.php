<?php

namespace Tests\Feature\Transfers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTransferTest extends TestCase
{
    use RefreshDatabase;

    private Branch $bole;
    private Branch $cmc;
    private User $admin;
    private User $boleManager;
    private User $cmcManager;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->bole = Branch::factory()->create(['name' => 'Bole Branch', 'status' => 'active']);
        $this->cmc = Branch::factory()->create(['name' => 'CMC Branch', 'status' => 'active']);
        $this->boleManager = User::factory()->branchManager()->create(['branch_id' => $this->bole->id]);
        $this->cmcManager = User::factory()->branchManager()->create(['branch_id' => $this->cmc->id]);
        $this->customer = User::factory()->customer()->create();
    }

    public function test_admin_can_approve_transfer_request(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'branch_id' => $this->bole->id]);
        $this->assertDatabaseHas('branches', ['id' => $this->cmc->id]);

        $transferDate = now()->addDays(5)->toDateString();

        $created = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
            'reason' => 'High demand',
        ]);

        if ($created->getStatusCode() !== 201) {
            $this->fail("Expected 201, got {$created->getStatusCode()}. Response: {$created->getContent()}");
        }

        $created->assertStatus(201);
        $transferId = $created->json('data.id');
        $this->assertNotNull($transferId);

        $approved = $this->actingAs($this->admin, 'sanctum')->putJson("/api/vehicle-transfers/{$transferId}/approve");
        $approved->assertStatus(200);

        $this->assertDatabaseHas('vehicle_transfers', [
            'id' => $transferId,
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => Vehicle::STATUS_UNAVAILABLE,
        ]);
    }

    public function test_duplicate_active_transfer_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $transferDate = now()->addDays(5)->toDateString();

        $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ])->assertStatus(201);

        $duplicate = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ]);

        $duplicate->assertStatus(409);
        $duplicate->assertJsonFragment(['message' => 'Vehicle already has an active transfer.']);
    }

    public function test_same_source_and_destination_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $transferDate = now()->addDays(5)->toDateString();

        $response = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->bole->id,
            'transfer_date' => $transferDate,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Source and destination branches must be different.']);
    }

    public function test_inactive_destination_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $inactiveBranch = Branch::factory()->create(['status' => 'inactive']);

        $transferDate = now()->addDays(5)->toDateString();

        $response = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $inactiveBranch->id,
            'transfer_date' => $transferDate,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Destination branch is inactive.']);
    }

    public function test_unauthorized_manager_cannot_request_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $transferDate = now()->addDays(5)->toDateString();

        $response = $this->actingAs($this->cmcManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ]);

        $response->assertStatus(403);
    }

    public function test_active_rental_vehicle_is_blocked(): void
    {
        $vehicle = Vehicle::factory()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_RENTED,
        ]);

        $transferDate = now()->addDays(5)->toDateString();

        $response = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ]);

        $response->assertStatus(422);
    }

    public function test_future_confirmed_booking_blocks_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $transferDay = now()->addDays(10);

        Booking::factory()->configure()->create([
            'vehicle_id' => $vehicle->id,
            'branch_id' => $this->bole->id,
            'status' => Booking::STATUS_CONFIRMED,
            'pickup_date' => $transferDay->copy()->subDays(1),
            'return_date' => $transferDay->copy()->addDays(1),
        ]);

        $transferDate = $transferDay->toDateString();

        $response = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'This vehicle has an upcoming confirmed booking and cannot be transferred.']);
    }

    public function test_maintenance_blocks_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        Maintenance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
        ]);

        $transferDate = now()->addDays(5)->toDateString();

        $response = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Vehicle is currently under maintenance and cannot be transferred.']);
    }

    public function test_full_transfer_lifecycle_updates_vehicle_branch_and_customer_visibility(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
            'registration_number' => 'ET-12345',
        ]);

        $transferDate = now()->addDays(5)->toDateString();

        $created = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ]);
        $created->assertStatus(201);

        $transferId = $created->json('data.id');

        // Approved
        $this->actingAs($this->admin, 'sanctum')->putJson("/api/vehicle-transfers/{$transferId}/approve")->assertStatus(200);

        // Customer should not see vehicle at destination while transfer not completed.
        $beforeVehicles = $this->actingAs($this->customer)->getJson('/api/vehicles?branch_id=' . $this->cmc->id . '&available_only=true');
        $this->assertFalse(collect($beforeVehicles->json('data'))->contains(fn ($v) => $v['id'] === $vehicle->id));

        // In transit — vehicle moves to destination branch (still unavailable).
        $this->actingAs($this->boleManager, 'sanctum')->putJson("/api/vehicle-transfers/{$transferId}/in-transit")->assertStatus(200);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'branch_id' => $this->cmc->id,
            'status' => Vehicle::STATUS_UNAVAILABLE,
        ]);

        // Source branch should no longer list this vehicle.
        $sourceVehicles = $this->actingAs($this->customer)->getJson('/api/vehicles?branch_id=' . $this->bole->id);
        $this->assertFalse(collect($sourceVehicles->json('data'))->contains(fn ($v) => $v['id'] === $vehicle->id));

        // Completed (destination confirms arrival)
        $this->actingAs($this->cmcManager, 'sanctum')->putJson("/api/vehicle-transfers/{$transferId}/complete")->assertStatus(200);

        $this->assertDatabaseHas('vehicle_transfers', [
            'id' => $transferId,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'branch_id' => $this->cmc->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $afterVehicles = $this->actingAs($this->customer)->getJson('/api/vehicles?branch_id=' . $this->cmc->id . '&available_only=true');
        $this->assertTrue(collect($afterVehicles->json('data'))->contains(fn ($v) => $v['id'] === $vehicle->id));
    }

    public function test_admin_can_execute_full_transfer_in_one_step(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $transferDate = now()->addDays(3)->toDateString();

        $created = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ]);
        $created->assertStatus(201);
        $transferId = $created->json('data.id');

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/execute")
            ->assertStatus(200);

        $this->assertDatabaseHas('vehicle_transfers', [
            'id' => $transferId,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'branch_id' => $this->cmc->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $sourceVehicles = $this->actingAs($this->customer)->getJson('/api/vehicles?branch_id=' . $this->bole->id);
        $this->assertFalse(collect($sourceVehicles->json('data'))->contains(fn ($v) => $v['id'] === $vehicle->id));

        $destVehicles = $this->actingAs($this->customer)->getJson('/api/vehicles?branch_id=' . $this->cmc->id . '&available_only=true');
        $this->assertTrue(collect($destVehicles->json('data'))->contains(fn ($v) => $v['id'] === $vehicle->id));
    }

    public function test_reject_requires_reason(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $transferDate = now()->addDays(5)->toDateString();

        $created = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ]);
        $created->assertStatus(201);

        $transferId = $created->json('data.id');

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/vehicle-transfers/{$transferId}/reject", []);
        $response->assertStatus(422);
    }

    public function test_invalid_status_transition_complete_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $transferDate = now()->addDays(5)->toDateString();

        $created = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
        ]);
        $transferId = $created->json('data.id');

        $response = $this->actingAs($this->cmcManager, 'sanctum')->putJson("/api/vehicle-transfers/{$transferId}/complete");
        $response->assertStatus(422);
    }
}

