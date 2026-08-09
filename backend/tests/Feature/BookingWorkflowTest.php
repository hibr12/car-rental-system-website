<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();
    }

    public function test_booking_workflow_pending_to_confirmed(): void
    {
        $vehicle = Vehicle::factory()->available()->create();

        $this->actingAs($this->customer);

        $response = $this->postJson('/api/bookings', [
            'vehicle_id' => $vehicle->id,
            'pickup_date' => now()->addDay()->format('Y-m-d'),
            'return_date' => now()->addDays(3)->format('Y-m-d'),
            'pickup_location' => 'Addis Ababa',
            'return_location' => 'Addis Ababa',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('bookings', [
            'vehicle_id' => $vehicle->id,
            'status' => 'pending',
        ]);

        $booking = Booking::where('vehicle_id', $vehicle->id)->first();

        $this->actingAs($this->admin);

        $confirmResponse = $this->putJson('/api/admin/bookings/' . $booking->id . '/confirm');
        $confirmResponse->assertOk();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_booking_workflow_pending_to_rejected(): void
    {
        $vehicle = Vehicle::factory()->available()->create();

        $this->actingAs($this->customer);

        $response = $this->postJson('/api/bookings', [
            'vehicle_id' => $vehicle->id,
            'pickup_date' => now()->addDay()->format('Y-m-d'),
            'return_date' => now()->addDays(3)->format('Y-m-d'),
            'pickup_location' => 'Addis Ababa',
            'return_location' => 'Addis Ababa',
        ]);

        $response->assertStatus(201);

        $booking = Booking::where('vehicle_id', $vehicle->id)->first();

        $this->actingAs($this->admin);

        $rejectResponse = $this->putJson('/api/admin/bookings/' . $booking->id . '/reject');
        $rejectResponse->assertOk();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'rejected',
        ]);
    }

    public function test_booking_workflow_confirmed_to_active(): void
    {
        $vehicle = Vehicle::factory()->available()->create();
        $booking = Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'confirmed',
        ]);

        $this->actingAs($this->admin);

        $response = $this->putJson('/api/admin/bookings/' . $booking->id . '/pickup');
        $response->assertOk();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => 'rented',
        ]);
    }

    public function test_booking_workflow_active_to_completed(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'rented']);
        $booking = Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);

        $response = $this->putJson('/api/admin/bookings/' . $booking->id . '/return');
        $response->assertOk();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => 'available',
        ]);
    }
}
