<?php

namespace Tests\Feature\Payments;

use App\Exceptions\PaymentVerificationRetryableException;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\ChapaLiveTestHelpers;
use Tests\TestCase;

/**
 * Live Chapa test-API integration for payment verification.
 *
 * Run with: CHAPA_LIVE_TESTS=true php artisan test --group=chapa-live
 */
#[Group('chapa-live')]
class PaymentChapaLiveTest extends TestCase
{
    use ChapaLiveTestHelpers;
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private Branch $branch;
    private Booking $booking;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipUnlessChapaLiveTests();

        $category = Category::factory()->create();
        $this->branch = Branch::factory()->create(['code' => 'LIVE-PV']);
        $this->customer = User::factory()->customer()->create([
            'email' => 'chapa.live.' . uniqid('', true) . '@testmail.com',
        ]);
        $this->admin = User::factory()->admin()->create();

        $vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'branch_id' => $this->branch->id,
            'rental_price_per_day' => 100,
        ]);

        $this->booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'branch_id' => $this->branch->id,
            'total_price' => 200,
            'status' => Booking::STATUS_PAYMENT_REQUIRED,
            'payment_status' => Booking::PAYMENT_STATUS_PENDING,
            'branch_approval_status' => Booking::APPROVAL_APPROVED,
            'admin_approval_status' => Booking::APPROVAL_NOT_REQUIRED,
        ]);

        $this->paymentService = app(PaymentService::class);
    }

    public function test_live_initialize_then_verify_unpaid_stays_processing(): void
    {
        $result = $this->paymentService->initializePayment(
            ['booking_id' => $this->booking->id],
            $this->customer->id
        );

        $this->assertNotEmpty($result['checkout_url']);
        $this->assertNotEmpty($result['tx_ref']);

        $payment = $this->paymentService->verifyPayment(
            $result['tx_ref'],
            $this->admin,
            'manual_verify'
        );

        $this->assertEquals(Payment::STATUS_PROCESSING, $payment->status);
        $this->assertEquals(Payment::VERIFICATION_GATEWAY_PENDING, $payment->verification_status);
        $this->booking->refresh();
        $this->assertEquals(Booking::STATUS_PAYMENT_PROCESSING, $this->booking->normalizeStatus());
    }

    public function test_live_success_matching_amount_is_verified_via_service_and_api(): void
    {
        $txRef = $this->uniqueTxRef('APEX-LIVE-OK');
        $payment = $this->makeOnlinePayment($this->booking, $this->customer, $txRef, 200.00);
        $this->chargeTelebirrTestPayment($txRef, 200.00);

        $result = $this->paymentService->verifyPayment($txRef, $this->admin, 'manual_verify');

        $this->assertEquals(Payment::STATUS_PAID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_VERIFIED, $result->verification_status);
        $this->assertEquals(200.0, (float) $result->paid_amount);
        $this->booking->refresh();
        $this->assertContains($this->booking->normalizeStatus(), [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_READY_FOR_PICKUP,
        ]);

        $token = $this->admin->createToken('live-test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payments/' . $payment->id . '/verify')
            ->assertOk()
            ->assertJsonPath('data.status', Payment::STATUS_PAID)
            ->assertJsonPath('data.verification_status', Payment::VERIFICATION_VERIFIED);
    }

    public function test_live_amount_mismatch_is_invalid(): void
    {
        $txRef = $this->uniqueTxRef('APEX-LIVE-SHORT');
        $this->makeOnlinePayment($this->booking, $this->customer, $txRef, 300.00);
        $this->chargeTelebirrTestPayment($txRef, 200.00);

        $result = $this->paymentService->verifyPayment($txRef, $this->admin, 'manual_verify');

        $this->assertEquals(Payment::STATUS_INVALID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_AMOUNT_MISMATCH, $result->verification_status);
        $this->assertEquals(Payment::MISMATCH_UNDERPAYMENT, $result->mismatch_reason);
        $this->booking->refresh();
        $this->assertEquals(Booking::STATUS_PAYMENT_REQUIRED, $this->booking->normalizeStatus());
    }

    public function test_live_unknown_tx_ref_is_retryable(): void
    {
        $txRef = $this->uniqueTxRef('APEX-LIVE-MISSING');
        $this->makeOnlinePayment($this->booking, $this->customer, $txRef, 200.00);

        $this->expectException(PaymentVerificationRetryableException::class);
        $this->paymentService->verifyPayment($txRef, $this->admin, 'manual_verify');
    }

    public function test_live_idempotent_verify_of_already_paid(): void
    {
        $txRef = $this->uniqueTxRef('APEX-LIVE-IDEM');
        $this->makeOnlinePayment($this->booking, $this->customer, $txRef, 200.00, [
            'status' => Payment::STATUS_PAID,
            'verification_status' => Payment::VERIFICATION_VERIFIED,
            'paid_amount' => 200,
        ]);

        $result = $this->paymentService->verifyPayment($txRef, $this->admin, 'manual_verify');

        $this->assertEquals(Payment::STATUS_PAID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_VERIFIED, $result->verification_status);
    }
}
