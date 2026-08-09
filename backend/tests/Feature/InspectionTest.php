<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Booking $booking;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->staff()->create();
        $this->vehicle = Vehicle::factory()->available()->create();
        $this->booking = Booking::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_staff_can_create_inspection(): void
    {
        $token = $this->staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/inspections', [
                'booking_id' => $this->booking->id,
                'vehicle_id' => $this->vehicle->id,
                'inspection_type' => 'pickup',
                'notes' => 'All good.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('inspections', [
            'booking_id' => $this->booking->id,
            'inspection_type' => 'pickup',
            'inspected_by' => $this->staff->id,
        ]);
    }

    public function test_staff_can_view_inspections(): void
    {
        Inspection::create([
            'booking_id' => $this->booking->id,
            'vehicle_id' => $this->vehicle->id,
            'inspected_by' => $this->staff->id,
            'inspection_type' => 'pickup',
            'inspected_at' => now(),
        ]);

        $token = $this->staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/inspections');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_customer_cannot_create_inspection(): void
    {
        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/inspections', [
                'booking_id' => $this->booking->id,
                'vehicle_id' => $this->vehicle->id,
                'inspection_type' => 'pickup',
                'notes' => 'All good.',
            ]);

        $response->assertStatus(403);
    }

    public function test_inspection_stores_fields_correctly(): void
    {
        $token = $this->staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/inspections', [
                'booking_id' => $this->booking->id,
                'vehicle_id' => $this->vehicle->id,
                'inspection_type' => 'return',
                'mileage_at_inspection' => 50000,
                'fuel_level_full' => true,
                'has_damage' => false,
                'notes' => 'Return complete.',
                'condition_rating' => 'good',
            ]);

        $response->assertStatus(201);

        $inspection = Inspection::where('booking_id', $this->booking->id)->first();
        $this->assertEquals('return', $inspection->inspection_type);
        $this->assertEquals(50000, $inspection->mileage_at_inspection);
        $this->assertTrue($inspection->fuel_level_full);
        $this->assertFalse($inspection->has_damage);
        $this->assertEquals('good', $inspection->condition_rating);
    }
}
