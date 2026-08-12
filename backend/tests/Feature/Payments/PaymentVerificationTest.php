<?php

namespace Tests\Feature\Payments;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ChapaService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private User $manager;
    private User $otherManager;
    private Branch $branch;
    private Branch $otherBranch;
    private Booking $booking;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::factory()->create();
        $this->branch = Branch::factory()->create(['code' => 'CMC-PV']);
        $this->otherBranch = Branch::factory()->create(['code' => 'BOL-PV']);

        $this->customer = User::factory()->customer()->create();
        $this->admin = User::factory()->admin()->create();
        $this->manager = User::factory()->branchManager()->create(['branch_id' => $this->branch->id]);
        $this->otherManager = User::factory()->branchManager()->create(['branch_id' => $this->otherBranch->id]);

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

    private function makeOnlinePayment(array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'booking_id' => $this->booking->id,
            'attempt_number' => 1,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 200,
            'expected_amount' => 200,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_ONLINE_PAYMENT,
            'gateway' => Payment::GATEWAY_CHAPA,
            'transaction_reference' => 'APEX-TEST-TX-001',
            'status' => Payment::STATUS_PROCESSING,
            'verification_status' => Payment::VERIFICATION_UNVERIFIED,
        ], $overrides));
    }

    private function mockChapa(array $result): void
    {
        $mock = Mockery::mock(ChapaService::class);
        $mock->shouldReceive('verifyTransaction')
            ->andReturn($result);
        $this->app->instance(ChapaService::class, $mock);
        $this->paymentService = app(PaymentService::class);
    }

    public function test_chapa_success_matching_amount_is_verified(): void
    {
        $payment = $this->makeOnlinePayment();
        $this->mockChapa([
            'status' => 'success',
            'amount' => 200.00,
            'currency' => 'ETB',
            'tx_ref' => 'APEX-TEST-TX-001',
            'reference' => 'CHAPA-REF-1',
            'raw' => ['status' => 'success', 'amount' => 200, 'currency' => 'ETB', 'tx_ref' => 'APEX-TEST-TX-001'],
        ]);

        $result = $this->paymentService->verifyPayment('APEX-TEST-TX-001', $this->admin, 'manual_verify');

        $this->assertEquals(Payment::STATUS_PAID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_VERIFIED, $result->verification_status);
        $this->assertEquals(200.0, (float) $result->paid_amount);
        $this->booking->refresh();
        $this->assertContains($this->booking->normalizeStatus(), [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_READY_FOR_PICKUP,
        ]);
    }

    public function test_chapa_success_amount_mismatch_is_invalid(): void
    {
        $payment = $this->makeOnlinePayment();
        $this->mockChapa([
            'status' => 'success',
            'amount' => 150.00,
            'currency' => 'ETB',
            'tx_ref' => 'APEX-TEST-TX-001',
            'reference' => 'CHAPA-REF-2',
            'raw' => ['status' => 'success', 'amount' => 150, 'currency' => 'ETB', 'tx_ref' => 'APEX-TEST-TX-001'],
        ]);

        $result = $this->paymentService->verifyPayment('APEX-TEST-TX-001', $this->admin, 'manual_verify');

        $this->assertEquals(Payment::STATUS_INVALID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_AMOUNT_MISMATCH, $result->verification_status);
        $this->assertEquals(150.0, (float) $result->paid_amount);
        $this->booking->refresh();
        $this->assertNotEquals(Booking::STATUS_CONFIRMED, $this->booking->status);
        $this->assertNotEquals(Booking::STATUS_CONFIRMED, $this->booking->normalizeStatus());
    }

    public function test_chapa_overpayment_is_invalid(): void
    {
        $this->makeOnlinePayment();
        $this->mockChapa([
            'status' => 'success',
            'amount' => 250.00,
            'currency' => 'ETB',
            'tx_ref' => 'APEX-TEST-TX-001',
            'reference' => 'CHAPA-REF-3',
            'raw' => ['status' => 'success', 'amount' => 250],
        ]);

        $result = $this->paymentService->verifyPayment('APEX-TEST-TX-001', $this->admin);

        $this->assertEquals(Payment::STATUS_INVALID, $result->status);
        $this->assertEquals(Payment::MISMATCH_OVERPAYMENT, $result->mismatch_reason);
    }

    public function test_currency_mismatch_is_invalid(): void
    {
        $this->makeOnlinePayment();
        $this->mockChapa([
            'status' => 'success',
            'amount' => 200.00,
            'currency' => 'USD',
            'tx_ref' => 'APEX-TEST-TX-001',
            'reference' => 'CHAPA-REF-4',
            'raw' => ['status' => 'success', 'currency' => 'USD'],
        ]);

        $result = $this->paymentService->verifyPayment('APEX-TEST-TX-001', $this->admin);

        $this->assertEquals(Payment::STATUS_INVALID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_CURRENCY_MISMATCH, $result->verification_status);
    }

    public function test_reference_mismatch_is_invalid(): void
    {
        $this->makeOnlinePayment();
        $this->mockChapa([
            'status' => 'success',
            'amount' => 200.00,
            'currency' => 'ETB',
            'tx_ref' => 'APEX-OTHER-REF',
            'reference' => 'CHAPA-REF-5',
            'raw' => ['tx_ref' => 'APEX-OTHER-REF'],
        ]);

        $result = $this->paymentService->verifyPayment('APEX-TEST-TX-001', $this->admin);

        $this->assertEquals(Payment::STATUS_INVALID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_REFERENCE_MISMATCH, $result->verification_status);
    }

    public function test_chapa_failed_sets_gateway_failed(): void
    {
        $this->makeOnlinePayment();
        $this->mockChapa([
            'status' => 'failed',
            'amount' => 0,
            'currency' => 'ETB',
            'tx_ref' => 'APEX-TEST-TX-001',
            'reference' => null,
            'raw' => ['status' => 'failed'],
        ]);

        $result = $this->paymentService->verifyPayment('APEX-TEST-TX-001', $this->admin);

        $this->assertEquals(Payment::STATUS_FAILED, $result->status);
        $this->assertEquals(Payment::VERIFICATION_GATEWAY_FAILED, $result->verification_status);
    }

    public function test_chapa_pending_stays_processing(): void
    {
        $this->makeOnlinePayment();
        $this->mockChapa([
            'status' => 'pending',
            'amount' => 0,
            'currency' => 'ETB',
            'tx_ref' => 'APEX-TEST-TX-001',
            'reference' => null,
            'raw' => ['status' => 'pending'],
        ]);

        $result = $this->paymentService->verifyPayment('APEX-TEST-TX-001', $this->admin);

        $this->assertEquals(Payment::STATUS_PROCESSING, $result->status);
        $this->assertEquals(Payment::VERIFICATION_GATEWAY_PENDING, $result->verification_status);
    }

    public function test_cash_exact_amount_is_manually_confirmed(): void
    {
        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'attempt_number' => 1,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 200,
            'expected_amount' => 200,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_CASH,
            'transaction_reference' => 'CASH-1',
            'status' => Payment::STATUS_CASH_PENDING,
            'verification_status' => Payment::VERIFICATION_UNVERIFIED,
        ]);

        $result = $this->paymentService->confirmCashPayment($payment, $this->manager, 200.00);

        $this->assertEquals(Payment::STATUS_PAID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_MANUALLY_CONFIRMED, $result->verification_status);
        $this->assertEquals($this->manager->id, $result->confirmed_by);
    }

    public function test_cash_short_payment_is_invalid(): void
    {
        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'attempt_number' => 1,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 200,
            'expected_amount' => 200,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_CASH,
            'transaction_reference' => 'CASH-2',
            'status' => Payment::STATUS_CASH_PENDING,
            'verification_status' => Payment::VERIFICATION_UNVERIFIED,
        ]);

        $result = $this->paymentService->confirmCashPayment($payment, $this->manager, 150.00);

        $this->assertEquals(Payment::STATUS_INVALID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_AMOUNT_MISMATCH, $result->verification_status);
    }

    public function test_cross_branch_manager_cannot_verify(): void
    {
        $payment = $this->makeOnlinePayment();
        $token = $this->otherManager->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payments/' . $payment->id . '/verify')
            ->assertStatus(403);
    }

    public function test_cross_branch_manager_cannot_confirm_cash(): void
    {
        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'attempt_number' => 1,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 200,
            'expected_amount' => 200,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_CASH,
            'transaction_reference' => 'CASH-3',
            'status' => Payment::STATUS_CASH_PENDING,
            'verification_status' => Payment::VERIFICATION_UNVERIFIED,
        ]);

        $token = $this->otherManager->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/payments/' . $payment->id . '/confirm-cash', [
                'amount_received' => 200,
            ])
            ->assertStatus(403);
    }

    public function test_customer_cannot_confirm_cash(): void
    {
        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'attempt_number' => 1,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 200,
            'expected_amount' => 200,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_CASH,
            'transaction_reference' => 'CASH-4',
            'status' => Payment::STATUS_CASH_PENDING,
            'verification_status' => Payment::VERIFICATION_UNVERIFIED,
        ]);

        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/payments/' . $payment->id . '/confirm-cash', [
                'amount_received' => 200,
            ])
            ->assertStatus(403);
    }

    public function test_frontend_amount_tampering_is_ignored_on_cash_create(): void
    {
        $token = $this->customer->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payments', [
                'booking_id' => $this->booking->id,
                'amount' => 1,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();
        $this->assertEquals(200.0, (float) $response->json('data.expected_amount'));
        $this->assertEquals(200.0, (float) $response->json('data.amount'));
    }

    public function test_duplicate_verified_payment_blocked(): void
    {
        $first = $this->makeOnlinePayment([
            'status' => Payment::STATUS_PAID,
            'verification_status' => Payment::VERIFICATION_VERIFIED,
            'paid_amount' => 200,
            'transaction_reference' => 'APEX-FIRST',
        ]);

        $second = $this->makeOnlinePayment([
            'attempt_number' => 2,
            'transaction_reference' => 'APEX-SECOND',
            'status' => Payment::STATUS_PROCESSING,
        ]);

        $this->mockChapa([
            'status' => 'success',
            'amount' => 200.00,
            'currency' => 'ETB',
            'tx_ref' => 'APEX-SECOND',
            'reference' => 'CHAPA-DUP',
            'raw' => [],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->paymentService->verifyPayment('APEX-SECOND', $this->admin);
    }

    public function test_idempotent_verify_of_already_paid(): void
    {
        $this->makeOnlinePayment([
            'status' => Payment::STATUS_PAID,
            'verification_status' => Payment::VERIFICATION_VERIFIED,
            'paid_amount' => 200,
        ]);

        $this->mockChapa([
            'status' => 'success',
            'amount' => 200,
            'currency' => 'ETB',
            'tx_ref' => 'APEX-TEST-TX-001',
            'reference' => 'X',
            'raw' => [],
        ]);

        $result = $this->paymentService->verifyPayment('APEX-TEST-TX-001', $this->admin);
        $this->assertEquals(Payment::STATUS_PAID, $result->status);
    }

    public function test_refund_preserves_history_and_uses_refund_states(): void
    {
        $payment = $this->makeOnlinePayment([
            'status' => Payment::STATUS_PAID,
            'verification_status' => Payment::VERIFICATION_VERIFIED,
            'paid_amount' => 200,
        ]);

        $result = $this->paymentService->refundPayment($payment, $this->admin, 200, 'Customer cancelled');

        $this->assertEquals(Payment::STATUS_REFUNDED, $result->status);
        $this->assertEquals(200.0, (float) $result->refund_amount);
        $this->assertNotNull($result->refunded_at);
    }

    public function test_partial_refund(): void
    {
        $payment = $this->makeOnlinePayment([
            'status' => Payment::STATUS_PAID,
            'verification_status' => Payment::VERIFICATION_VERIFIED,
            'paid_amount' => 200,
        ]);

        $result = $this->paymentService->refundPayment($payment, $this->admin, 100, 'Partial');

        $this->assertEquals(Payment::STATUS_PARTIALLY_REFUNDED, $result->status);
        $this->assertEquals(100.0, (float) $result->refund_amount);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
