<?php

namespace Tests\Feature\Reviews;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\AdminNewReview;
use App\Notifications\BookingCompleted;
use App\Notifications\ReviewReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $otherCustomer;
    private User $admin;
    private User $branchManager;
    private Branch $branch;
    private Branch $otherBranch;
    private Vehicle $vehicle;
    private Booking $completedBooking;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->branch = Branch::factory()->create(['name' => 'CMC Branch']);
        $this->otherBranch = Branch::factory()->create(['name' => 'Bole Branch']);

        $this->branchManager = User::factory()->create([
            'role' => User::ROLE_BRANCH_MANAGER,
            'branch_id' => $this->branch->id,
        ]);

        $this->customer = User::factory()->customer()->create();
        $this->otherCustomer = User::factory()->customer()->create();

        $category = Category::factory()->create();
        $this->vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->completedBooking = Booking::factory()->completed()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->token = $this->customer->createToken('auth-token')->plainTextToken;
    }

    private function validReviewPayload(array $overrides = []): array
    {
        return array_merge([
            'overall_rating' => 5,
            'vehicle_rating' => 5,
            'cleanliness_rating' => 5,
            'staff_rating' => 4,
            'value_rating' => 5,
            'comment' => 'Excellent rental experience.',
        ], $overrides);
    }

    public function test_customer_can_review_completed_booking(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $this->completedBooking->id . '/reviews', $this->validReviewPayload());

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'overall_rating' => 5,
            'status' => Review::STATUS_PUBLISHED,
        ]);
    }

    public function test_cannot_review_pending_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => Booking::STATUS_PENDING_PAYMENT,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $booking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(422)
            ->assertJson(['message' => 'Only completed bookings can be reviewed.']);
    }

    public function test_cannot_review_confirmed_not_picked_up(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'picked_up_at' => null,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $booking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(422);
    }

    public function test_cannot_review_active_rental(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => Booking::STATUS_ACTIVE,
            'picked_up_at' => now()->subDay(),
            'returned_at' => null,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $booking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(422);
    }

    public function test_cannot_review_returned_but_not_completed(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => Booking::STATUS_RETURN_PENDING,
            'picked_up_at' => now()->subDays(2),
            'returned_at' => now()->subDay(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $booking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(422)
            ->assertJson(['message' => 'Only completed bookings can be reviewed.']);
    }

    public function test_cannot_review_cancelled_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => Booking::STATUS_CANCELLED,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $booking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(422);
    }

    public function test_cannot_review_another_customers_booking(): void
    {
        $otherToken = $this->otherCustomer->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->postJson('/api/bookings/' . $this->completedBooking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(403);
    }

    public function test_duplicate_review_rejected(): void
    {
        Review::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $this->completedBooking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(422)
            ->assertJson(['message' => 'This booking has already been reviewed.']);
    }

    public function test_ratings_must_be_1_to_5(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $this->completedBooking->id . '/reviews', $this->validReviewPayload(['overall_rating' => 6]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['overall_rating']);
    }

    public function test_comment_max_length_enforced(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $this->completedBooking->id . '/reviews', $this->validReviewPayload([
                'comment' => str_repeat('a', 1001),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_branch_and_vehicle_ids_derived_from_booking(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $this->completedBooking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $this->completedBooking->id,
            'vehicle_id' => $this->completedBooking->vehicle_id,
            'branch_id' => $this->completedBooking->branch_id,
            'user_id' => $this->customer->id,
        ]);
    }

    public function test_branch_manager_only_sees_own_branch_reviews(): void
    {
        Review::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
        ]);

        $otherVehicle = Vehicle::factory()->available()->create([
            'category_id' => Category::factory()->create()->id,
            'branch_id' => $this->otherBranch->id,
        ]);
        $otherBooking = Booking::factory()->completed()->create([
            'user_id' => $this->otherCustomer->id,
            'vehicle_id' => $otherVehicle->id,
            'branch_id' => $this->otherBranch->id,
        ]);
        Review::factory()->create([
            'vehicle_id' => $otherVehicle->id,
            'booking_id' => $otherBooking->id,
            'branch_id' => $this->otherBranch->id,
            'user_id' => $this->otherCustomer->id,
        ]);

        $managerToken = $this->branchManager->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $managerToken)
            ->getJson('/api/admin/reviews');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($this->branch->id, $response->json('data.0.branch_id'));
    }

    public function test_admin_sees_all_branch_reviews(): void
    {
        Review::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
        ]);

        $secondBooking = Booking::factory()->completed()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
        ]);
        Review::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $secondBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
        ]);

        $adminToken = $this->admin->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->getJson('/api/admin/reviews')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_hidden_review_not_public(): void
    {
        Review::factory()->hidden()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
        ]);

        $this->getJson('/api/vehicles/' . $this->vehicle->id . '/reviews')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_archived_review_not_public(): void
    {
        Review::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
            'status' => Review::STATUS_ARCHIVED,
        ]);

        $this->getJson('/api/vehicles/' . $this->vehicle->id . '/reviews')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_flagged_review_not_public(): void
    {
        Review::factory()->flagged()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
        ]);

        $this->getJson('/api/vehicles/' . $this->vehicle->id . '/reviews')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_published_review_contributes_to_rating(): void
    {
        Review::factory()->published()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
            'overall_rating' => 4,
        ]);

        $response = $this->getJson('/api/vehicles/' . $this->vehicle->id . '/reviews');

        $response->assertOk()
            ->assertJsonPath('meta.average_rating', 4)
            ->assertJsonCount(1, 'data');
    }

    public function test_verified_rental_flag_on_completed_booking_review(): void
    {
        $review = Review::factory()->published()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
        ]);

        $this->getJson('/api/vehicles/' . $this->vehicle->id . '/reviews')
            ->assertOk()
            ->assertJsonPath('data.0.verified_rental', true);
    }

    public function test_admin_cannot_modify_customer_rating_via_update(): void
    {
        $review = Review::factory()->published()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
            'overall_rating' => 5,
        ]);

        $adminToken = $this->admin->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->putJson('/api/reviews/' . $review->id, ['overall_rating' => 2])
            ->assertStatus(403);
    }

    public function test_admin_response_works(): void
    {
        $review = Review::factory()->published()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
        ]);

        $adminToken = $this->admin->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson('/api/admin/reviews/' . $review->id . '/respond', [
                'admin_response' => 'Thank you for choosing Apex Rentals.',
            ])
            ->assertOk()
            ->assertJsonPath('data.admin_response', 'Thank you for choosing Apex Rentals.');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'admin_response_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_moderate_review_status(): void
    {
        $review = Review::factory()->published()->create([
            'vehicle_id' => $this->vehicle->id,
            'booking_id' => $this->completedBooking->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->customer->id,
        ]);

        $adminToken = $this->admin->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->patchJson('/api/admin/reviews/' . $review->id . '/status', ['status' => Review::STATUS_HIDDEN])
            ->assertOk()
            ->assertJsonPath('data.status', Review::STATUS_HIDDEN);
    }

    public function test_comment_is_sanitized(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $this->completedBooking->id . '/reviews', $this->validReviewPayload([
                'comment' => '<script>alert("xss")</script>Great car!',
            ]))
            ->assertStatus(201);

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $this->completedBooking->id,
            'comment' => 'alert("xss")Great car!',
        ]);
    }

    public function test_booking_completed_notification_sent(): void
    {
        Notification::fake();

        $this->customer->notify(new BookingCompleted($this->completedBooking));

        Notification::assertSentTo($this->customer, BookingCompleted::class);
    }

    public function test_review_submission_notifies_branch(): void
    {
        Notification::fake();

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/bookings/' . $this->completedBooking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(201);

        Notification::assertSentTo($this->admin, AdminNewReview::class);
        Notification::assertSentTo($this->branchManager, AdminNewReview::class);
    }

    public function test_eligible_bookings_endpoint(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/reviews/eligible-bookings');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->completedBooking->id);
    }

    public function test_unauthenticated_user_cannot_create_review(): void
    {
        $this->postJson('/api/bookings/' . $this->completedBooking->id . '/reviews', $this->validReviewPayload())
            ->assertStatus(401);
    }
}
