<?php

namespace Tests\Feature\Bookings;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingWorkflowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private User $branchManager;
    private User $otherManager;
    private Branch $branch;
    private Branch $otherBranch;
    private Vehicle $vehicle;
    private BookingWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflow = app(BookingWorkflowService::class);

        $category = Category::factory()->create();
        $this->branch = Branch::factory()->create(['name' => 'CMC Branch', 'code' => 'CMC-01']);
        $this->otherBranch = Branch::factory()->create(['name' => 'Bole Branch', 'code' => 'BOL-01']);

        $this->customer = User::factory()->customer()->create();
        $this->admin = User::factory()->admin()->create();
        $this->branchManager = User::factory()->branchManager()->create(['branch_id' => $this->branch->id]);
        $this->otherManager = User::factory()->branchManager()->create(['branch_id' => $this->otherBranch->id]);

        $this->vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'branch_id' => $this->branch->id,
            'rental_price_per_day' => 100,
        ]);
    }

    private function createBooking(array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'user_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'status' => Booking::STATUS_PENDING_BRANCH_APPROVAL,
            'payment_status' => Booking::PAYMENT_STATUS_NOT_REQUIRED,
            'branch_approval_status' => Booking::APPROVAL_PENDING,
            'admin_approval_status' => Booking::APPROVAL_NOT_REQUIRED,
            'admin_approval_required' => false,
            'pickup_date' => Carbon::tomorrow()->addDay(),
            'return_date' => Carbon::tomorrow()->addDays(3),
            'number_of_days' => 2,
            'total_price' => 200,
            'subtotal' => 200,
        ], $overrides));
    }

    private function markPaidVerified(Booking $booking): Payment
    {
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'branch_id' => $booking->branch_id,
            'amount' => $booking->total_price,
            'expected_amount' => $booking->total_price,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_ONLINE_PAYMENT,
            'transaction_reference' => 'TXN-TEST-' . $booking->id,
            'status' => Payment::STATUS_PAID,
            'verification_status' => Payment::VERIFICATION_VERIFIED,
            'paid_at' => now(),
            'verified_at' => now(),
        ]);

        $booking->update(['payment_status' => Booking::PAYMENT_STATUS_PAID]);

        return $payment;
    }

    public function test_new_booking_starts_awaiting_branch_approval(): void
    {
        $token = $this->customer->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'CMC',
                'return_location' => 'CMC',
                'pickup_date' => Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00'),
                'return_date' => Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00'),
            ]);

        $response->assertCreated();
        $this->assertEquals(Booking::STATUS_PENDING_BRANCH_APPROVAL, $response->json('data.status'));
        $this->assertEquals(Booking::PAYMENT_STATUS_NOT_REQUIRED, $response->json('data.payment_status'));
        $this->assertEquals(Booking::APPROVAL_PENDING, $response->json('data.branch_approval_status'));
    }

    public function test_unpaid_booking_can_be_branch_approved(): void
    {
        $booking = $this->createBooking();

        $result = $this->workflow->approveBranch($booking, $this->branchManager);

        $this->assertEquals(Booking::APPROVAL_APPROVED, $result->branch_approval_status);
        $this->assertEquals(Booking::STATUS_PAYMENT_REQUIRED, $result->status);
        $this->assertEquals(Booking::PAYMENT_STATUS_PENDING, $result->payment_status);
    }

    public function test_branch_approval_without_payment_does_not_confirm(): void
    {
        $booking = $this->createBooking();

        $result = $this->workflow->approveBranch($booking, $this->branchManager);

        $this->assertNotEquals(Booking::STATUS_CONFIRMED, $result->status);
        $this->assertFalse($result->isPaymentSatisfied());
    }

    public function test_verified_payment_after_approval_confirms_booking(): void
    {
        $booking = $this->createBooking();
        $approved = $this->workflow->approveBranch($booking, $this->branchManager);
        $this->markPaidVerified($approved);

        $advanced = $this->workflow->advanceAfterPaymentVerified($approved->fresh()->load('payments'));

        $this->assertContains($advanced->status, [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_READY_FOR_PICKUP,
        ]);
    }

    public function test_payment_before_branch_approval_does_not_auto_confirm(): void
    {
        $booking = $this->createBooking();
        $this->markPaidVerified($booking);

        $advanced = $this->workflow->advanceAfterPaymentVerified($booking->fresh()->load('payments'));

        $this->assertEquals(Booking::STATUS_PENDING_BRANCH_APPROVAL, $advanced->status);
    }

    public function test_branch_pending_cannot_become_confirmed_without_payment(): void
    {
        $booking = $this->createBooking([
            'branch_approval_status' => Booking::APPROVAL_PENDING,
        ]);

        $this->assertFalse($booking->canBecomeConfirmed());
    }

    public function test_admin_required_pending_cannot_become_confirmed_without_payment(): void
    {
        $booking = $this->createBooking([
            'status' => Booking::STATUS_PENDING_ADMIN_APPROVAL,
            'branch_approval_status' => Booking::APPROVAL_APPROVED,
            'admin_approval_status' => Booking::APPROVAL_PENDING,
            'admin_approval_required' => true,
        ]);

        $this->assertFalse($booking->fresh()->canBecomeConfirmed());
    }

    public function test_admin_approved_becomes_payment_required(): void
    {
        $booking = $this->createBooking([
            'status' => Booking::STATUS_PENDING_ADMIN_APPROVAL,
            'branch_approval_status' => Booking::APPROVAL_APPROVED,
            'admin_approval_status' => Booking::APPROVAL_PENDING,
            'admin_approval_required' => true,
        ]);

        $result = $this->workflow->approveAdmin($booking, $this->admin);

        $this->assertEquals(Booking::APPROVAL_APPROVED, $result->admin_approval_status);
        $this->assertEquals(Booking::STATUS_PAYMENT_REQUIRED, $result->status);
        $this->assertEquals(Booking::PAYMENT_STATUS_PENDING, $result->payment_status);
    }

    public function test_paid_booking_cannot_be_branch_rejected(): void
    {
        $booking = $this->createBooking([
            'status' => Booking::STATUS_PAYMENT_REQUIRED,
            'branch_approval_status' => Booking::APPROVAL_APPROVED,
            'payment_status' => Booking::PAYMENT_STATUS_PAID,
        ]);
        $this->markPaidVerified($booking);

        $this->expectException(\InvalidArgumentException::class);
        $this->workflow->rejectBranch($booking->fresh()->load('payments'), $this->branchManager, 'Vehicle unavailable');
    }

    public function test_unpaid_booking_can_be_rejected(): void
    {
        $booking = $this->createBooking();

        $result = $this->workflow->rejectBranch($booking, $this->branchManager, 'Vehicle unavailable');

        $this->assertEquals(Booking::STATUS_REJECTED, $result->status);
        $this->assertEquals(Booking::PAYMENT_STATUS_NOT_REQUIRED, $result->payment_status);
    }

    public function test_pickup_blocked_while_approvals_pending(): void
    {
        $booking = $this->createBooking([
            'status' => Booking::STATUS_CONFIRMED,
            'branch_approval_status' => Booking::APPROVAL_PENDING,
            'admin_approval_status' => Booking::APPROVAL_NOT_REQUIRED,
        ]);
        $this->markPaidVerified($booking);

        $this->expectException(\InvalidArgumentException::class);
        $this->workflow->markPickedUp($booking->fresh()->load(['payments', 'vehicle']), $this->branchManager, [
            'identity_verification_status' => Booking::DOC_VERIFIED,
            'license_verification_status' => Booking::DOC_VERIFIED,
            'pickup_mileage' => 1000,
            'pickup_fuel_level' => 'full',
        ]);
    }

    public function test_active_booking_can_be_returned(): void
    {
        $booking = $this->createBooking([
            'status' => Booking::STATUS_ACTIVE,
            'branch_approval_status' => Booking::APPROVAL_APPROVED,
            'admin_approval_status' => Booking::APPROVAL_NOT_REQUIRED,
            'payment_status' => Booking::PAYMENT_STATUS_PAID,
            'pickup_mileage' => 1000,
        ]);
        $this->markPaidVerified($booking);
        $this->vehicle->update(['status' => 'rented']);

        $result = $this->workflow->markReturned($booking->fresh()->load('vehicle'), $this->branchManager, [
            'return_mileage' => 1200,
            'return_fuel_level' => 'half',
        ]);

        $this->assertEquals(Booking::STATUS_COMPLETED, $result->status);
        $this->assertEquals('available', $result->vehicle->status);
    }

    public function test_completed_booking_allows_review(): void
    {
        $booking = $this->createBooking([
            'status' => Booking::STATUS_COMPLETED,
            'branch_approval_status' => Booking::APPROVAL_APPROVED,
            'payment_status' => Booking::PAYMENT_STATUS_PAID,
        ]);

        $token = $this->customer->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/vehicles/' . $this->vehicle->id . '/reviews', [
                'booking_id' => $booking->id,
                'vehicle_id' => $this->vehicle->id,
                'rating' => 5,
                'comment' => 'Great rental experience',
            ]);

        $response->assertCreated();
    }

    public function test_cancelled_booking_cannot_become_active(): void
    {
        $booking = $this->createBooking(['status' => Booking::STATUS_CANCELLED]);

        $this->expectException(\InvalidArgumentException::class);
        $this->workflow->markPickedUp($booking, $this->admin, [
            'identity_verification_status' => Booking::DOC_VERIFIED,
            'license_verification_status' => Booking::DOC_VERIFIED,
            'pickup_mileage' => 1,
            'pickup_fuel_level' => 'full',
        ]);
    }

    public function test_rejected_booking_cannot_become_active(): void
    {
        $booking = $this->createBooking(['status' => Booking::STATUS_REJECTED]);

        $this->expectException(\InvalidArgumentException::class);
        $this->workflow->markPickedUp($booking, $this->admin, [
            'identity_verification_status' => Booking::DOC_VERIFIED,
            'license_verification_status' => Booking::DOC_VERIFIED,
            'pickup_mileage' => 1,
            'pickup_fuel_level' => 'full',
        ]);
    }

    public function test_cross_branch_manager_cannot_approve(): void
    {
        $booking = $this->createBooking();

        $this->expectException(\InvalidArgumentException::class);
        $this->workflow->approveBranch($booking, $this->otherManager);
    }

    public function test_branch_approval_fails_when_vehicle_branch_mismatch(): void
    {
        $booking = $this->createBooking([
            'branch_id' => $this->otherBranch->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->workflow->approveBranch($booking, $this->admin);
    }

    public function test_customer_cannot_access_another_customers_booking(): void
    {
        $booking = $this->createBooking();
        $other = User::factory()->customer()->create();
        $token = $other->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/bookings/' . $booking->id)
            ->assertStatus(403);
    }

    public function test_staff_cannot_approve_branch(): void
    {
        $staff = User::factory()->staff()->create(['branch_id' => $this->branch->id]);
        $booking = $this->createBooking();

        $token = $staff->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/bookings/' . $booking->id . '/confirm')
            ->assertStatus(403);
    }

    public function test_vehicle_cannot_be_double_booked(): void
    {
        $this->createBooking([
            'status' => Booking::STATUS_CONFIRMED,
            'pickup_date' => Carbon::tomorrow()->addDays(2),
            'return_date' => Carbon::tomorrow()->addDays(6),
        ]);

        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/bookings', [
                'vehicle_id' => $this->vehicle->id,
                'pickup_location' => 'CMC',
                'return_location' => 'CMC',
                'pickup_date' => Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00'),
                'return_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00'),
            ])
            ->assertStatus(422);
    }

    public function test_payment_branch_matches_booking_branch(): void
    {
        $booking = $this->createBooking();
        $approved = $this->workflow->approveBranch($booking, $this->branchManager);
        $payment = $this->markPaidVerified($approved);

        $this->assertEquals($booking->branch_id, $payment->branch_id);
    }

    public function test_duplicate_branch_approval_is_rejected(): void
    {
        $booking = $this->createBooking();
        $this->workflow->approveBranch($booking, $this->branchManager);

        $this->expectException(\InvalidArgumentException::class);
        $this->workflow->approveBranch($booking->fresh(), $this->branchManager);
    }

    public function test_review_before_completed_is_rejected(): void
    {
        $booking = $this->createBooking(['status' => Booking::STATUS_ACTIVE]);
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/vehicles/' . $this->vehicle->id . '/reviews', [
                'booking_id' => $booking->id,
                'vehicle_id' => $this->vehicle->id,
                'rating' => 4,
                'comment' => 'Too early',
            ])
            ->assertStatus(422);
    }

    public function test_return_on_confirmed_booking_is_rejected(): void
    {
        $booking = $this->createBooking([
            'status' => Booking::STATUS_CONFIRMED,
            'branch_approval_status' => Booking::APPROVAL_APPROVED,
            'admin_approval_status' => Booking::APPROVAL_NOT_REQUIRED,
        ]);
        $this->markPaidVerified($booking);

        $this->expectException(\InvalidArgumentException::class);
        $this->workflow->markReturned($booking->fresh(), $this->branchManager, [
            'return_mileage' => 100,
            'return_fuel_level' => 'full',
        ]);
    }

    public function test_full_happy_path_approval_payment_to_active(): void
    {
        $booking = $this->createBooking([
            'pickup_date' => now()->addHours(2),
            'return_date' => now()->addDays(2),
        ]);

        $paymentRequired = $this->workflow->approveBranch($booking, $this->branchManager);
        $this->assertEquals(Booking::STATUS_PAYMENT_REQUIRED, $paymentRequired->status);

        $this->markPaidVerified($paymentRequired);
        $confirmed = $this->workflow->advanceAfterPaymentVerified($paymentRequired->fresh()->load('payments'));
        $this->assertContains($confirmed->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_READY_FOR_PICKUP]);

        if ($confirmed->status === Booking::STATUS_CONFIRMED) {
            $confirmed = $this->workflow->preparePickup($confirmed, $this->branchManager);
        }

        $active = $this->workflow->markPickedUp($confirmed->fresh()->load(['payments', 'vehicle']), $this->branchManager, [
            'identity_verification_status' => Booking::DOC_VERIFIED,
            'license_verification_status' => Booking::DOC_VERIFIED,
            'pickup_mileage' => 5000,
            'pickup_fuel_level' => 'full',
        ]);

        $this->assertEquals(Booking::STATUS_ACTIVE, $active->status);
        $this->assertEquals('rented', $active->vehicle->status);
    }

    public function test_customer_gets_pay_action_after_branch_approval(): void
    {
        $booking = $this->createBooking();
        $approved = $this->workflow->approveBranch($booking, $this->branchManager);

        $customerView = $this->withHeader('Authorization', 'Bearer ' . $this->customer->createToken('t')->plainTextToken)
            ->getJson('/api/bookings/' . $approved->id);

        $customerView->assertOk();
        $this->assertContains('pay', $customerView->json('data.allowed_actions'));
        $this->assertEquals(Booking::STATUS_PAYMENT_REQUIRED, $customerView->json('data.status'));
        $this->assertEquals(Booking::PAYMENT_STATUS_PENDING, $customerView->json('data.payment_status'));
    }

    public function test_customer_does_not_get_pay_action_before_branch_approval(): void
    {
        $booking = $this->createBooking();

        $customerView = $this->withHeader('Authorization', 'Bearer ' . $this->customer->createToken('t')->plainTextToken)
            ->getJson('/api/bookings/' . $booking->id);

        $customerView->assertOk();
        $this->assertNotContains('pay', $customerView->json('data.allowed_actions'));
    }
}
