<?php

namespace Tests\Feature\Bookings;

use App\Models\Booking;
use App\Models\Category;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private Vehicle $vehicle;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();

        $category = Category::factory()->create();

        $this->vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'rental_price_per_day' => 100,
        ]);

        $this->token = $this->customer->createToken('auth-token')->plainTextToken;
    }

    public function test_customer_can_create_booking(): void
    {
        $pickupDate = Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00');
        $returnDate = Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'Main Office',
                'return_location' => 'Main Office',
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'booking_reference',
                    'vehicle',
                    'pickup_location',
                    'return_location',
                    'pickup_date',
                    'return_date',
                    'number_of_days',
                    'price_per_day',
                    'subtotal',
                    'total_price',
                    'status',
                    'payment_status',
                ],
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_return_date_must_be_after_pickup_date(): void
    {
        $pickupDate = Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00');
        $returnDate = Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'Main Office',
                'return_location' => 'Main Office',
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
            ]);

        $response->assertStatus(422);
    }

    public function test_invalid_dates_are_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'Main Office',
                'return_location' => 'Main Office',
                'pickup_date' => 'invalid-date',
                'return_date' => 'invalid-date',
            ]);

        $response->assertStatus(422);
    }

    public function test_overlapping_bookings_are_rejected(): void
    {
        Booking::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'user_id' => User::factory()->customer()->create()->id,
            'pickup_date' => Carbon::tomorrow()->addDays(2)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(6)->format('Y-m-d 10:00:00'),
            'status' => 'confirmed',
        ]);

        $pickupDate = Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00');
        $returnDate = Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'Main Office',
                'return_location' => 'Main Office',
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Vehicle is already booked for the selected dates.',
            ]);
    }

    public function test_vehicle_under_maintenance_cannot_be_booked(): void
    {
        $this->vehicle->update(['status' => 'maintenance']);

        $pickupDate = Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00');
        $returnDate = Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'Main Office',
                'return_location' => 'Main Office',
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
            ]);

        $response->assertStatus(422);
    }

    public function test_total_price_is_calculated_by_backend(): void
    {
        $pickupDate = Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00');
        $returnDate = Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'Main Office',
                'return_location' => 'Main Office',
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
            ]);

        $response->assertStatus(201);
        $responseData = $response->json('data');

        $this->assertEquals(100, $responseData['price_per_day']);
        $this->assertEquals(4, $responseData['number_of_days']);
        $this->assertEquals(400, $responseData['subtotal']);
        $this->assertEquals(400, $responseData['total_price']);
    }

    public function test_customer_can_cancel_eligible_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/bookings/' . $booking->id . '/cancel');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_customer_can_view_own_bookings(): void
    {
        Booking::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/bookings');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_customer_cannot_access_another_users_booking(): void
    {
        $otherUser = User::factory()->customer()->create();
        $otherToken = $otherUser->createToken('auth-token')->plainTextToken;

        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->getJson('/api/bookings/' . $booking->id);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_booking(): void
    {
        $response = $this->postJson('/api/bookings', [
            'vehicle_id' => $this->vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00'),
        ]);

        $response->assertStatus(401);
    }

    public function test_staff_can_confirm_booking(): void
    {
        $staff = User::factory()->staff()->create();
        $staffToken = $staff->createToken('auth-token')->plainTextToken;

        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $staffToken)
            ->putJson('/api/admin/bookings/' . $booking->id . '/confirm');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Booking confirmed successfully',
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_admin_can_manage_booking_workflow(): void
    {
        $adminToken = $this->admin->createToken('auth-token')->plainTextToken;

        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'pending',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->putJson('/api/admin/bookings/' . $booking->id . '/confirm')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->putJson('/api/admin/bookings/' . $booking->id . '/pickup')
            ->assertOk();

        $this->assertDatabaseHas('vehicles', [
            'id' => $this->vehicle->id,
            'status' => 'rented',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->putJson('/api/admin/bookings/' . $booking->id . '/return')
            ->assertOk();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $this->vehicle->id,
            'status' => 'available',
        ]);
    }

    public function test_admin_can_reject_booking_with_reason(): void
    {
        $adminToken = $this->admin->createToken('auth-token')->plainTextToken;

        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->putJson('/api/admin/bookings/' . $booking->id . '/reject', [
                'reason' => 'Vehicle unavailable',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'rejected',
        ]);

        $booking->refresh();
        $this->assertStringContainsString('Vehicle unavailable', $booking->notes);
    }
}