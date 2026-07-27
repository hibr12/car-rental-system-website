<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->vehicle = Vehicle::factory()->create([
            'status' => 'available',
            'rental_price_per_day' => 100.00
        ]);
    }

    /** @test */
    public function customer_can_create_booking()
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'Airport',
                'return_location' => 'Hotel',
                'pickup_date' => now()->addDays(1)->format('Y-m-d'),
                'return_date' => now()->addDays(3)->format('Y-m-d'),
                'notes' => 'Test booking'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'booking_reference',
                    'status',
                    'payment_status',
                    'total_price'
                ]
            ]);
