<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->vehicle = Vehicle::factory()->create([
            'status' => 'available',
            'rental_price_per_day' => 100.00,
        ]);
    }

    public function test_customer_can_create_booking(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'Airport',
                'return_location' => 'Hotel',
                'pickup_date' => now()->addDays(1)->format('Y-m-d H:i:s'),
                'return_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'notes' => 'Test booking',
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
                    'total_price',
                ],
            ]);

        $this->assertEquals('payment_required', $response->json('data.status'));
        $this->assertEquals('pending', $response->json('data.payment_status'));
    }
}
