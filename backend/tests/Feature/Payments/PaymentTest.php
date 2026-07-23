<?php

namespace Tests\Feature\Payments;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private Booking $booking;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();

        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'rental_price_per_day' => 100,
        ]);

        $this->booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 400,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        $this->token = $this->customer->createToken('auth-token')->plainTextToken;
    }

    public function test_payment_can_be_created(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/payments', [
                'booking_id' => $this->booking->id,
                'amount' => 400,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'booking_id',
                    'amount',
                    'payment_method',
                    'transaction_reference',
                    'status',
                    'paid_at',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'amount' => 400,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_payment_amount_must_match_booking_total(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/payments', [
                'booking_id' => $this->booking->id,
                'amount' => 100,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Payment amount must match the booking total.',
            ]);
    }

    public function test_unauthorized_user_cannot_pay_for_others_booking(): void
    {
        $otherUser = User::factory()->customer()->create();
        $otherToken = $otherUser->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->postJson('/api/payments', [
                'booking_id' => $this->booking->id,
                'amount' => 400,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized payment for this booking.',
            ]);
    }

    public function test_user_can_view_own_payments(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/payments', [
                'booking_id' => $this->booking->id,
                'amount' => 400,
                'payment_method' => 'card',
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/payments');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_users_cannot_access_unauthorized_payment_records(): void
    {
        $postResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/payments', [
                'booking_id' => $this->booking->id,
                'amount' => 400,
                'payment_method' => 'card',
            ]);
        $postResponse->assertStatus(201);

        $payment = Payment::where('booking_id', $this->booking->id)->first();
        $this->assertNotNull($payment, 'Payment should exist');
        $this->assertEquals($this->customer->id, $payment->getAttribute('user_id'), 'user_id should match customer');
        $this->assertEquals($this->customer->id, $payment->user_id, 'user_id magic prop should match');

        $otherUser = User::factory()->customer()->create();
        $this->assertNotEquals($this->customer->id, $otherUser->id, 'other user should have different id');

        $response = $this->actingAs($otherUser)
            ->getJson('/api/payments/' . $payment->id);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_payment(): void
    {
        $response = $this->postJson('/api/payments', [
            'booking_id' => $this->booking->id,
            'amount' => 400,
            'payment_method' => 'card',
        ]);

        $response->assertStatus(401);
    }
}