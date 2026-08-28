<?php

namespace Tests\Feature\Transfers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VehicleTransferTest extends TestCase
{
    use RefreshDatabase;

    private Branch $bole;
    private Branch $cmc;
    private User $admin;
    private User $fleetManager;
    private User $boleManager;
    private User $cmcManager;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->admin = User::factory()->admin()->create();
        $this->fleetManager = User::factory()->fleetManager()->create();
        $this->bole = Branch::factory()->create(['name' => 'Bole Branch', 'status' => 'active']);
        $this->cmc = Branch::factory()->create(['name' => 'CMC Branch', 'status' => 'active']);
        $this->boleManager = User::factory()->branchManager()->create(['branch_id' => $this->bole->id]);
        $this->cmcManager = User::factory()->branchManager()->create(['branch_id' => $this->cmc->id]);
        $this->customer = User::factory()->customer()->create();
    }

    private function createTransferRequest(Vehicle $vehicle, ?string $transferDate = null): int
    {
        $transferDate = $transferDate ?? now()->addDays(5)->toDateString();

        $response = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDate,
            'reason' => 'High demand',
        ]);

        $response->assertStatus(201);
        return (int) $response->json('data.id');
    }

    public function test_branch_manager_can_request_transfer_from_own_branch(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $transferId = $this->createTransferRequest($vehicle);

        $this->assertDatabaseHas('vehicle_transfers', [
            'id' => $transferId,
            'status' => VehicleTransfer::STATUS_PENDING,
            'from_branch_id' => $this->bole->id,
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'branch_id' => $this->bole->id,
        ]);
    }

    public function test_fleet_manager_can_approve_valid_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $transferId = $this->createTransferRequest($vehicle);

        $this->actingAs($this->fleetManager, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/approve")
            ->assertStatus(200);

        $this->assertDatabaseHas('vehicle_transfers', [
            'id' => $transferId,
            'status' => VehicleTransfer::STATUS_READY_FOR_RELEASE,
            'approved_by' => $this->fleetManager->id,
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_TRANSFER_PENDING,
        ]);
    }

    public function test_branch_manager_cannot_approve_own_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $transferId = $this->createTransferRequest($vehicle);

        $this->actingAs($this->boleManager, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/approve")
            ->assertStatus(403);
    }

    public function test_destination_branch_manager_cannot_approve_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $transferId = $this->createTransferRequest($vehicle);

        $this->actingAs($this->cmcManager, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/approve")
            ->assertStatus(403);
    }

    public function test_duplicate_active_transfer_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $this->createTransferRequest($vehicle);

        $duplicate = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => now()->addDays(5)->toDateString(),
        ]);

        $duplicate->assertStatus(409);
        $duplicate->assertJsonFragment(['message' => 'Vehicle already has an active transfer.']);
    }

    public function test_same_source_and_destination_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);

        $response = $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->bole->id,
            'transfer_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Destination branch must be different from the current branch.']);
    }

    public function test_unauthorized_manager_cannot_request_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);

        $this->actingAs($this->cmcManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => now()->addDays(5)->toDateString(),
        ])->assertStatus(403);
    }

    public function test_active_rental_vehicle_is_blocked(): void
    {
        $vehicle = Vehicle::factory()->create([
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_RENTED,
        ]);

        $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => now()->addDays(5)->toDateString(),
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'Vehicle cannot be transferred while it is being rented.']);
    }

    public function test_maintenance_blocks_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);

        Maintenance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => now()->addDays(5)->toDateString(),
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'Vehicle is currently under maintenance and cannot be transferred.']);
    }

    public function test_future_confirmed_booking_blocks_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $transferDay = now()->addDays(10);

        Booking::factory()->configure()->create([
            'vehicle_id' => $vehicle->id,
            'branch_id' => $this->bole->id,
            'status' => Booking::STATUS_CONFIRMED,
            'pickup_date' => $transferDay->copy()->subDays(1),
            'return_date' => $transferDay->copy()->addDays(1),
        ]);

        $this->actingAs($this->boleManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $vehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => $transferDay->toDateString(),
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'This vehicle has an upcoming confirmed booking and cannot be transferred.']);
    }

    public function test_full_transfer_lifecycle_keeps_branch_until_completion(): void
    {
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $this->bole->id,
            'registration_number' => 'ET-12345',
        ]);

        $transferId = $this->createTransferRequest($vehicle);

        $this->actingAs($this->fleetManager, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/approve")
            ->assertStatus(200);

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'branch_id' => $this->bole->id]);

        $this->actingAs($this->boleManager, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/in-transit", [
                'source_odometer' => 45230,
                'source_fuel_level' => 80,
                'source_condition' => 'good',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'branch_id' => $this->bole->id,
            'status' => Vehicle::STATUS_TRANSFER_IN_TRANSIT,
        ]);

        $beforeVehicles = $this->actingAs($this->customer)->getJson('/api/vehicles?branch_id=' . $this->cmc->id . '&available_only=true');
        $this->assertFalse(collect($beforeVehicles->json('data'))->contains(fn ($v) => $v['id'] === $vehicle->id));

        $this->actingAs($this->cmcManager, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/receive", [
                'destination_odometer' => 45250,
                'destination_fuel_level' => 78,
                'destination_condition' => 'good',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('vehicle_transfers', [
            'id' => $transferId,
            'status' => VehicleTransfer::STATUS_COMPLETED,
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'branch_id' => $this->cmc->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ]);

        $afterVehicles = $this->actingAs($this->customer)->getJson('/api/vehicles?branch_id=' . $this->cmc->id . '&available_only=true');
        $this->assertTrue(collect($afterVehicles->json('data'))->contains(fn ($v) => $v['id'] === $vehicle->id));
    }

    public function test_customer_cannot_book_vehicle_with_active_transfer(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $this->createTransferRequest($vehicle);

        $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'vehicle_id' => $vehicle->id,
            'branch_id' => $this->bole->id,
            'pickup_location' => 'Bole',
            'return_location' => 'Bole',
            'pickup_date' => now()->addDays(7)->toDateString(),
            'return_date' => now()->addDays(9)->toDateString(),
        ])->assertStatus(422);
    }

    public function test_fleet_manager_can_see_all_transfers(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $this->createTransferRequest($vehicle);

        $response = $this->actingAs($this->fleetManager, 'sanctum')->getJson('/api/vehicle-transfers');
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_branch_manager_sees_only_branch_transfers(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $this->createTransferRequest($vehicle);

        $boleResponse = $this->actingAs($this->boleManager, 'sanctum')->getJson('/api/vehicle-transfers');
        $boleResponse->assertStatus(200);
        $this->assertNotEmpty($boleResponse->json('data'));

        $otherBranch = Branch::factory()->create(['status' => 'active']);
        $otherVehicle = Vehicle::factory()->available()->create(['branch_id' => $otherBranch->id]);
        $otherManager = User::factory()->branchManager()->create(['branch_id' => $otherBranch->id]);

        $this->actingAs($otherManager, 'sanctum')->postJson('/api/vehicle-transfers', [
            'vehicle_id' => $otherVehicle->id,
            'to_branch_id' => $this->cmc->id,
            'transfer_date' => now()->addDays(4)->toDateString(),
        ])->assertStatus(201);

        $cmcResponse = $this->actingAs($this->cmcManager, 'sanctum')->getJson('/api/vehicle-transfers');
        $this->assertCount(2, $cmcResponse->json('data'));

        $isolatedResponse = $this->actingAs($otherManager, 'sanctum')->getJson('/api/vehicle-transfers');
        $this->assertCount(1, $isolatedResponse->json('data'));
    }

    public function test_reject_requires_reason(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $transferId = $this->createTransferRequest($vehicle);

        $this->actingAs($this->fleetManager, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/reject", [])
            ->assertStatus(422);
    }

    public function test_receive_with_damage_requires_fleet_completion(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $transferId = $this->createTransferRequest($vehicle);

        $this->actingAs($this->fleetManager, 'sanctum')->putJson("/api/vehicle-transfers/{$transferId}/approve");
        $this->actingAs($this->boleManager, 'sanctum')->putJson("/api/vehicle-transfers/{$transferId}/in-transit", [
            'source_odometer' => 1000,
        ]);

        $this->actingAs($this->cmcManager, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/receive", [
                'has_damage' => true,
                'damage_report' => 'Scratch on rear bumper',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('vehicle_transfers', [
            'id' => $transferId,
            'status' => VehicleTransfer::STATUS_RECEIVED_PENDING_INSPECTION,
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'branch_id' => $this->bole->id,
        ]);

        $this->actingAs($this->fleetManager, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/complete")
            ->assertStatus(200);

        $this->assertDatabaseHas('vehicle_transfers', [
            'id' => $transferId,
            'status' => VehicleTransfer::STATUS_COMPLETED,
        ]);
    }

    public function test_admin_can_execute_full_transfer_in_one_step(): void
    {
        $vehicle = Vehicle::factory()->available()->create(['branch_id' => $this->bole->id]);
        $transferId = $this->createTransferRequest($vehicle);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/vehicle-transfers/{$transferId}/execute")
            ->assertStatus(200);

        $this->assertDatabaseHas('vehicle_transfers', ['id' => $transferId, 'status' => 'completed']);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'branch_id' => $this->cmc->id, 'status' => Vehicle::STATUS_AVAILABLE]);
    }
}
