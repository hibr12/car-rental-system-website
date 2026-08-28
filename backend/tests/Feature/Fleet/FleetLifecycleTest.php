<?php

namespace Tests\Feature\Fleet;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleInspection;
use App\Services\BookingWorkflowService;
use App\Services\VehicleInspectionService;
use App\Services\VehicleStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_sets_vehicle_to_pending_inspection(): void
    {
        [$booking, $manager] = $this->activeBookingFixture();

        app(BookingWorkflowService::class)->markReturned($booking->fresh()->load('vehicle'), $manager, [
            'return_mileage' => 25000,
            'return_fuel_level' => 50,
        ]);

        $booking->vehicle->refresh();
        $this->assertEquals(Vehicle::STATUS_RETURN_PENDING_INSPECTION, $booking->vehicle->status);
        $this->assertDatabaseHas('vehicle_inspections', [
            'vehicle_id' => $booking->vehicle_id,
            'booking_id' => $booking->id,
            'inspection_type' => VehicleInspection::TYPE_POST_RETURN,
        ]);
    }

    public function test_inspection_pass_makes_vehicle_available(): void
    {
        [$booking, $manager] = $this->activeBookingFixture();

        $workflow = app(BookingWorkflowService::class);
        $workflow->markReturned($booking->fresh()->load('vehicle'), $manager, [
            'return_mileage' => 25000,
            'return_fuel_level' => 50,
        ]);

        $inspection = VehicleInspection::where('booking_id', $booking->id)->first();
        app(VehicleInspectionService::class)->complete($inspection, [
            'result' => VehicleInspection::RESULT_PASSED,
            'mileage' => 25000,
        ], $manager);

        $this->assertEquals(Vehicle::STATUS_AVAILABLE, $booking->vehicle->fresh()->status);
    }

    public function test_maintenance_completion_requires_inspection(): void
    {
        $fleet = User::factory()->fleetManager()->create();
        $vehicle = $this->makeVehicle();
        $token = $fleet->createToken('t')->plainTextToken;

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/maintenance', [
                'vehicle_id' => $vehicle->id,
                'branch_id' => $vehicle->branch_id,
                'title' => 'Oil change',
                'maintenance_type' => 'oil_change',
                'status' => 'in_progress',
                'start_date' => now()->toDateTimeString(),
            ])
            ->assertStatus(201);

        $maintenanceId = $create->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/maintenance/{$maintenanceId}", ['status' => 'completed'])
            ->assertOk();

        $this->assertEquals(Vehicle::STATUS_INSPECTION_REQUIRED, $vehicle->fresh()->status);
    }

    public function test_expired_document_blocks_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $vehicle = $this->makeVehicle();

        VehicleDocument::create([
            'vehicle_id' => $vehicle->id,
            'document_type' => VehicleDocument::TYPE_INSURANCE,
            'expiry_date' => now()->subDay()->toDateString(),
            'status' => VehicleDocument::STATUS_EXPIRED,
            'is_required' => true,
        ]);

        $token = $customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/bookings', [
                'vehicle_id' => $vehicle->id,
                'branch_id' => $vehicle->branch_id,
                'pickup_date' => now()->addDays(3)->toDateString(),
                'return_date' => now()->addDays(5)->toDateString(),
            ])
            ->assertStatus(422);
    }

    public function test_mileage_cannot_decrease_without_correction(): void
    {
        $fleet = User::factory()->fleetManager()->create();
        $vehicle = $this->makeVehicle(['mileage' => 30000]);
        $service = app(VehicleStatusService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->updateMileage($vehicle, 25000, $fleet, false);
    }

    private function activeBookingFixture(): array
    {
        $branch = Branch::factory()->create();
        $manager = User::factory()->branchManager()->create(['branch_id' => $branch->id]);
        $customer = User::factory()->customer()->create();
        $vehicle = $this->makeVehicle(['branch_id' => $branch->id, 'status' => Vehicle::STATUS_RENTED]);

        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'branch_id' => $branch->id,
            'status' => Booking::STATUS_ACTIVE,
            'pickup_mileage' => 24000,
        ]);

        return [$booking, $manager];
    }

    private function makeVehicle(array $overrides = []): Vehicle
    {
        $branch = Branch::factory()->create();
        $category = Category::factory()->create();

        return Vehicle::factory()->create(array_merge([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'status' => Vehicle::STATUS_AVAILABLE,
        ], $overrides));
    }
}
