<?php

namespace Tests\Feature\Reviews;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private Vehicle $vehicle;
    private Booking $completedBooking;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();

        $category = Category::factory()->create();
        $this->vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
        ]);

        $this->completedBooking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'completed',
        ]);

        $this->token = $this->customer->createToken('auth-token')->plainTextToken;
    }

    public function test_anyone_can_view_vehicle_reviews(): void
    {
        Review::factory()->count(3)->create([
            'vehicle_id' => $this->vehicle->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/vehicles/' . $this->vehicle->id . '/reviews');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_completed_customer_can_review_vehicle(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/vehicles/' . $this->vehicle->id . '/reviews', [
                'booking_id' => $this->completedBooking->id,
                'rating' => 5,
                'comment' => 'Excellent car!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Review created successfully',
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'rating' => 5,
        ]);
    }

    public function test_incomplete_bookings_cannot_create_reviews(): void
    {
        $pendingBooking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/vehicles/' . $this->vehicle->id . '/reviews', [
                'booking_id' => $pendingBooking->id,
                'rating' => 4,
                'comment' => 'Good car',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Only completed bookings can be reviewed.',
            ]);
    }

    public function test_duplicate_reviews_are_rejected(): void
    {
        Review::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/vehicles/' . $this->vehicle->id . '/reviews', [
                'booking_id' => $this->completedBooking->id,
                'rating' => 4,
                'comment' => 'Good car',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You have already reviewed this booking.',
            ]);
    }

    public function test_ratings_outside_1_5_are_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/vehicles/' . $this->vehicle->id . '/reviews', [
                'booking_id' => $this->completedBooking->id,
                'rating' => 6,
                'comment' => 'Great!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/vehicles/' . $this->vehicle->id . '/reviews', [
                'booking_id' => $this->completedBooking->id,
                'rating' => 0,
                'comment' => 'Bad!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_admin_can_delete_review(): void
    {
        $review = Review::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
        ]);

        $adminToken = $this->admin->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->deleteJson('/api/reviews/' . $review->id);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Review deleted successfully',
            ]);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_unauthenticated_user_cannot_create_review(): void
    {
        $response = $this->postJson('/api/vehicles/' . $this->vehicle->id . '/reviews', [
            'booking_id' => $this->completedBooking->id,
            'rating' => 5,
            'comment' => 'Great!',
        ]);

        $response->assertStatus(401);
    }
}