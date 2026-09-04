<?php

namespace Tests\Feature\Payments;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ChapaConfigValidator;
use App\Services\ChapaService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Proves that the same application code can run in TEST or LIVE mode
 * using only environment variable changes.
 *
 * All HTTP calls to Chapa are mocked — no real network requests.
 */
class ChapaConfigTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private Branch $branch;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::factory()->create();
        $this->branch = Branch::factory()->create(['code' => 'CFG-TST']);
        $this->customer = User::factory()->customer()->create(['name' => 'Test Customer', 'email' => 'test@example.com']);
        $this->admin = User::factory()->admin()->create();

        $vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'branch_id' => $this->branch->id,
            'rental_price_per_day' => 200,
        ]);

        $this->booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'branch_id' => $this->branch->id,
            'total_price' => 200,
            'subtotal' => 200,
            'additional_charges' => 0,
            'discount' => 0,
            'status' => Booking::STATUS_PAYMENT_REQUIRED,
            'payment_status' => Booking::PAYMENT_STATUS_PENDING,
            'branch_approval_status' => Booking::APPROVAL_APPROVED,
            'admin_approval_status' => Booking::APPROVAL_NOT_REQUIRED,
            'admin_approval_required' => false,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Configuration loading
    // ─────────────────────────────────────────────────────────────────────────

    public function test_test_mode_configuration_loads_correctly(): void
    {
        Config::set('services.chapa', [
            'mode' => 'test',
            'secret_key' => 'CHASECK_TEST-abc123',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'http://localhost:8000/api/payments/callback',
            'return_url' => 'http://localhost:5173/payments/status',
            'webhook_url' => 'http://localhost:8000/api/payments/chapa/webhook',
            'webhook_secret' => 'CHASECK_TEST-abc123',
        ]);

        $this->assertEquals('test', config('services.chapa.mode'));
        $this->assertEquals('CHASECK_TEST-abc123', config('services.chapa.secret_key'));
        $this->assertEquals('https://api.chapa.co', config('services.chapa.base_url'));
        $this->assertStringContainsString('/api/payments/callback', config('services.chapa.callback_url'));
        $this->assertStringContainsString('/payments/status', config('services.chapa.return_url'));
    }

    public function test_live_configuration_loads_correctly(): void
    {
        Config::set('services.chapa', [
            'mode' => 'live',
            'secret_key' => 'CHASECK-livekey123',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'https://api.apexrentals.com/api/payments/callback',
            'return_url' => 'https://apexrentals.com/payment/result',
            'webhook_url' => 'https://api.apexrentals.com/api/payments/chapa/webhook',
            'webhook_secret' => 'CHASECK-livekey123',
        ]);

        $this->assertEquals('live', config('services.chapa.mode'));
        $this->assertEquals('CHASECK-livekey123', config('services.chapa.secret_key'));
        $this->assertStringStartsWith('https://api.apexrentals.com', config('services.chapa.callback_url'));
        $this->assertStringStartsWith('https://apexrentals.com', config('services.chapa.return_url'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. ChapaConfigValidator — validation rules
    // ─────────────────────────────────────────────────────────────────────────

    public function test_validator_passes_for_complete_test_config(): void
    {
        Config::set('services.chapa', [
            'mode' => 'test',
            'secret_key' => 'CHASECK_TEST-abc123',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'http://localhost:8000/api/payments/callback',
            'return_url' => 'http://localhost:5173/payments/status',
            'webhook_url' => 'http://localhost:8000/api/payments/chapa/webhook',
            'webhook_secret' => 'CHASECK_TEST-abc123',
        ]);

        // Must not throw.
        app(ChapaConfigValidator::class)->validate();
        $this->assertTrue(true);
    }

    public function test_validator_passes_for_complete_live_config(): void
    {
        Config::set('services.chapa', [
            'mode' => 'live',
            'secret_key' => 'CHASECK-livekey123',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'https://api.apexrentals.com/api/payments/callback',
            'return_url' => 'https://apexrentals.com/payment/result',
            'webhook_url' => 'https://api.apexrentals.com/api/payments/chapa/webhook',
            'webhook_secret' => 'CHASECK-livekey123',
        ]);

        app(ChapaConfigValidator::class)->validate();
        $this->assertTrue(true);
    }

    public function test_missing_live_secret_key_throws(): void
    {
        Config::set('services.chapa', [
            'mode' => 'live',
            'secret_key' => '',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'https://api.apexrentals.com/api/payments/callback',
            'return_url' => 'https://apexrentals.com/payment/result',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/CHAPA_SECRET_KEY.*live mode/i');

        app(ChapaConfigValidator::class)->validate();
    }

    public function test_missing_live_callback_url_throws(): void
    {
        Config::set('services.chapa', [
            'mode' => 'live',
            'secret_key' => 'CHASECK-livekey123',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => '',
            'return_url' => 'https://apexrentals.com/payment/result',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/CHAPA_CALLBACK_URL.*live mode/i');

        app(ChapaConfigValidator::class)->validate();
    }

    public function test_live_mode_with_test_key_throws(): void
    {
        Config::set('services.chapa', [
            'mode' => 'live',
            'secret_key' => 'CHASECK_TEST-shouldNotBeHere',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'https://api.apexrentals.com/api/payments/callback',
            'return_url' => 'https://apexrentals.com/payment/result',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/live.*CHASECK_TEST-/i');

        app(ChapaConfigValidator::class)->validate();
    }

    public function test_invalid_mode_value_throws(): void
    {
        Config::set('services.chapa.mode', 'staging');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches("/CHAPA_MODE must be 'test' or 'live'/i");

        app(ChapaConfigValidator::class)->validate();
    }

    public function test_missing_test_key_logs_warning_not_exception(): void
    {
        Config::set('services.chapa', [
            'mode' => 'test',
            'secret_key' => '',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'http://localhost:8000/api/payments/callback',
            'return_url' => 'http://localhost:5173/payments/status',
        ]);

        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        // Must NOT throw — test mode is lenient about missing keys.
        app(ChapaConfigValidator::class)->validate();
        $this->assertTrue(true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. ChapaService — env-switch proves same code, different config
    // ─────────────────────────────────────────────────────────────────────────

    public function test_chapa_service_reflects_test_mode(): void
    {
        Config::set('services.chapa.mode', 'test');
        $service = app(ChapaService::class);
        $this->assertEquals('test', $service->getMode());
    }

    public function test_chapa_service_reflects_live_mode(): void
    {
        Config::set('services.chapa.mode', 'live');
        $service = app(ChapaService::class);
        $this->assertEquals('live', $service->getMode());
    }

    public function test_same_code_runs_in_test_mode_mocked(): void
    {
        Config::set('services.chapa', [
            'mode' => 'test',
            'secret_key' => 'CHASECK_TEST-abc123',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'http://localhost:8000/api/payments/callback',
            'return_url' => 'http://localhost:5173/payments/status',
            'webhook_url' => 'http://localhost:8000/api/payments/chapa/webhook',
            'webhook_secret' => 'CHASECK_TEST-abc123',
        ]);

        Http::fake([
            'api.chapa.co/v1/transaction/initialize' => Http::response([
                'status' => 'success',
                'data' => ['checkout_url' => 'https://checkout.chapa.co/test/pay'],
            ], 200),
        ]);

        $result = app(PaymentService::class)->initializePayment(
            ['booking_id' => $this->booking->id],
            $this->customer->id
        );

        $this->assertStringContainsString('chapa.co', $result['checkout_url']);
        $this->assertNotEmpty($result['tx_ref']);
    }

    public function test_same_code_runs_in_live_mode_mocked(): void
    {
        Config::set('services.chapa', [
            'mode' => 'live',
            'secret_key' => 'CHASECK-livekey123',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'https://api.apexrentals.com/api/payments/callback',
            'return_url' => 'https://apexrentals.com/payment/result',
            'webhook_url' => 'https://api.apexrentals.com/api/payments/chapa/webhook',
            'webhook_secret' => 'CHASECK-livekey123',
        ]);

        Http::fake([
            'api.chapa.co/v1/transaction/initialize' => Http::response([
                'status' => 'success',
                'data' => ['checkout_url' => 'https://checkout.chapa.co/live/pay'],
            ], 200),
        ]);

        $result = app(PaymentService::class)->initializePayment(
            ['booking_id' => $this->booking->id],
            $this->customer->id
        );

        $this->assertStringContainsString('chapa.co', $result['checkout_url']);
        $this->assertNotEmpty($result['tx_ref']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Callback / return URL is always env-driven
    // ─────────────────────────────────────────────────────────────────────────

    public function test_callback_url_comes_from_config_not_hardcode(): void
    {
        $expectedCallback = 'https://api.apexrentals.com/api/payments/callback';
        $expectedReturn = 'https://apexrentals.com/payment/result';

        Config::set('services.chapa', [
            'mode' => 'live',
            'secret_key' => 'CHASECK-livekey123',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => $expectedCallback,
            'return_url' => $expectedReturn,
            'webhook_url' => 'https://api.apexrentals.com/api/payments/chapa/webhook',
            'webhook_secret' => 'CHASECK-livekey123',
        ]);

        Http::fake([
            'api.chapa.co/v1/transaction/initialize' => function ($request) use ($expectedCallback, $expectedReturn) {
                $body = json_decode($request->body(), true);

                // The backend must have sent the config-driven URLs.
                $this->assertEquals($expectedCallback, $body['callback_url']);
                $this->assertStringStartsWith($expectedReturn, $body['return_url']);

                return Http::response([
                    'status' => 'success',
                    'data' => ['checkout_url' => 'https://checkout.chapa.co/live/pay'],
                ], 200);
            },
        ]);

        app(PaymentService::class)->initializePayment(
            ['booking_id' => $this->booking->id],
            $this->customer->id
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Successful Chapa payment (mocked)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_successful_chapa_payment_marks_paid_and_confirmed(): void
    {
        $this->setChapaTestConfig();

        $txRef = 'APEX-TEST-' . uniqid('', true);

        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'attempt_number' => 1,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 200.00,
            'expected_amount' => 200.00,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_ONLINE_PAYMENT,
            'gateway' => Payment::GATEWAY_CHAPA,
            'transaction_reference' => $txRef,
            'status' => Payment::STATUS_PROCESSING,
            'verification_status' => Payment::VERIFICATION_UNVERIFIED,
            'idempotency_key' => 'chapa:' . $this->booking->id . ':' . $txRef,
        ]);

        Http::fake([
            'api.chapa.co/v1/transaction/verify/*' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'success',
                    'amount' => 200.00,
                    'currency' => 'ETB',
                    'tx_ref' => $txRef,
                    'reference' => 'CHAPA-REF-' . uniqid('', true),
                ],
            ], 200),
        ]);

        $result = app(PaymentService::class)->verifyPayment($txRef, $this->admin, 'test');

        $this->assertEquals(Payment::STATUS_PAID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_VERIFIED, $result->verification_status);
        $this->assertNotNull($result->paid_at);
        $this->assertNotNull($result->verified_at);

        $this->booking->refresh();
        $this->assertContains($this->booking->normalizeStatus(), [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_READY_FOR_PICKUP,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Failed Chapa payment (mocked)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_failed_chapa_payment_keeps_booking_payable(): void
    {
        $this->setChapaTestConfig();

        $txRef = 'APEX-FAIL-' . uniqid('', true);
        $payment = $this->makeProcessingPayment($txRef, 200.00);

        Http::fake([
            'api.chapa.co/v1/transaction/verify/*' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'failed',
                    'amount' => 200.00,
                    'currency' => 'ETB',
                    'tx_ref' => $txRef,
                    'reference' => null,
                ],
            ], 200),
        ]);

        $result = app(PaymentService::class)->verifyPayment($txRef, $this->admin, 'test');

        $this->assertEquals(Payment::STATUS_FAILED, $result->status);

        $this->booking->refresh();
        $this->assertEquals(Booking::STATUS_PAYMENT_REQUIRED, $this->booking->normalizeStatus());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Amount mismatch
    // ─────────────────────────────────────────────────────────────────────────

    public function test_amount_mismatch_marks_invalid_not_paid(): void
    {
        $this->setChapaTestConfig();

        $txRef = 'APEX-MISMATCH-' . uniqid('', true);
        $this->makeProcessingPayment($txRef, 200.00);

        Http::fake([
            'api.chapa.co/v1/transaction/verify/*' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'success',
                    'amount' => 150.00,   // ← mismatch
                    'currency' => 'ETB',
                    'tx_ref' => $txRef,
                    'reference' => 'CHAPA-SHORT-REF',
                ],
            ], 200),
        ]);

        $result = app(PaymentService::class)->verifyPayment($txRef, $this->admin, 'test');

        $this->assertEquals(Payment::STATUS_INVALID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_AMOUNT_MISMATCH, $result->verification_status);
        $this->assertEquals(Payment::MISMATCH_UNDERPAYMENT, $result->mismatch_reason);

        $this->booking->refresh();
        $this->assertNotEquals(Booking::STATUS_CONFIRMED, $this->booking->normalizeStatus());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. Currency mismatch
    // ─────────────────────────────────────────────────────────────────────────

    public function test_currency_mismatch_marks_invalid(): void
    {
        $this->setChapaTestConfig();

        $txRef = 'APEX-CURR-' . uniqid('', true);
        $this->makeProcessingPayment($txRef, 200.00);

        Http::fake([
            'api.chapa.co/v1/transaction/verify/*' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'success',
                    'amount' => 200.00,
                    'currency' => 'USD',  // ← wrong currency
                    'tx_ref' => $txRef,
                    'reference' => 'CHAPA-CURR-REF',
                ],
            ], 200),
        ]);

        $result = app(PaymentService::class)->verifyPayment($txRef, $this->admin, 'test');

        $this->assertEquals(Payment::STATUS_INVALID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_CURRENCY_MISMATCH, $result->verification_status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. Reference mismatch
    // ─────────────────────────────────────────────────────────────────────────

    public function test_reference_mismatch_marks_invalid(): void
    {
        $this->setChapaTestConfig();

        $txRef = 'APEX-REF-' . uniqid('', true);
        $this->makeProcessingPayment($txRef, 200.00);

        Http::fake([
            'api.chapa.co/v1/transaction/verify/*' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'success',
                    'amount' => 200.00,
                    'currency' => 'ETB',
                    'tx_ref' => 'COMPLETELY-DIFFERENT-REF',  // ← mismatch
                    'reference' => 'CHAPA-WRONG',
                ],
            ], 200),
        ]);

        $result = app(PaymentService::class)->verifyPayment($txRef, $this->admin, 'test');

        $this->assertEquals(Payment::STATUS_INVALID, $result->status);
        $this->assertEquals(Payment::VERIFICATION_REFERENCE_MISMATCH, $result->verification_status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10. Duplicate callback / idempotency
    // ─────────────────────────────────────────────────────────────────────────

    public function test_duplicate_callback_does_not_double_pay(): void
    {
        $this->setChapaTestConfig();

        $txRef = 'APEX-DUP-' . uniqid('', true);
        $payment = $this->makeProcessingPayment($txRef, 200.00);

        $chapaVerifyResponse = Http::response([
            'status' => 'success',
            'data' => [
                'status' => 'success',
                'amount' => 200.00,
                'currency' => 'ETB',
                'tx_ref' => $txRef,
                'reference' => 'CHAPA-DUP-REF',
            ],
        ], 200);

        Http::fake([
            'api.chapa.co/v1/transaction/verify/*' => $chapaVerifyResponse,
        ]);

        $svc = app(PaymentService::class);

        $first = $svc->verifyPayment($txRef, $this->admin, 'callback');
        $this->assertEquals(Payment::STATUS_PAID, $first->status);

        // Second call must be idempotent — return same PAID payment without duplicate processing.
        Http::fake([
            'api.chapa.co/v1/transaction/verify/*' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'success',
                    'amount' => 200.00,
                    'currency' => 'ETB',
                    'tx_ref' => $txRef,
                    'reference' => 'CHAPA-DUP-REF',
                ],
            ], 200),
        ]);

        $second = $svc->verifyPayment($txRef, $this->admin, 'callback');
        $this->assertEquals(Payment::STATUS_PAID, $second->status);

        $paidCount = Payment::where('booking_id', $this->booking->id)
            ->where('status', Payment::STATUS_PAID)
            ->count();
        $this->assertEquals(1, $paidCount);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 11. Already-paid transaction rejected
    // ─────────────────────────────────────────────────────────────────────────

    public function test_already_paid_booking_cannot_be_paid_again(): void
    {
        $this->setChapaTestConfig();

        $this->booking->update(['payment_status' => Booking::PAYMENT_STATUS_PAID]);
        Payment::create([
            'booking_id' => $this->booking->id,
            'attempt_number' => 1,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 200.00,
            'expected_amount' => 200.00,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_ONLINE_PAYMENT,
            'gateway' => Payment::GATEWAY_CHAPA,
            'transaction_reference' => 'APEX-PAID-' . uniqid('', true),
            'status' => Payment::STATUS_PAID,
            'verification_status' => Payment::VERIFICATION_VERIFIED,
            'paid_amount' => 200.00,
            'paid_at' => now(),
            'verified_at' => now(),
            'idempotency_key' => 'chapa:already-paid',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/already been paid/i');

        app(PaymentService::class)->initializePayment(
            ['booking_id' => $this->booking->id],
            $this->customer->id
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 12. Cancelled booking cannot be paid
    // ─────────────────────────────────────────────────────────────────────────

    public function test_cancelled_booking_cannot_be_paid(): void
    {
        $this->setChapaTestConfig();

        $this->booking->update([
            'status' => Booking::STATUS_CANCELLED,
            'payment_status' => Booking::PAYMENT_STATUS_PENDING,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(PaymentService::class)->initializePayment(
            ['booking_id' => $this->booking->id],
            $this->customer->id
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 13. Unauthorized customer cannot pay another customer's booking
    // ─────────────────────────────────────────────────────────────────────────

    public function test_unauthorized_customer_cannot_pay_other_booking(): void
    {
        $this->setChapaTestConfig();

        $otherCustomer = User::factory()->customer()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/[Uu]nauthorized/');

        app(PaymentService::class)->initializePayment(
            ['booking_id' => $this->booking->id],
            $otherCustomer->id    // ← not the booking owner
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 14. Branch authorization — manager cannot access other branch payments
    // ─────────────────────────────────────────────────────────────────────────

    public function test_branch_manager_cannot_confirm_cash_for_other_branch(): void
    {
        $this->setChapaTestConfig();

        $otherBranch = Branch::factory()->create(['code' => 'OTHER-B']);
        $otherManager = User::factory()->branchManager()->create(['branch_id' => $otherBranch->id]);

        $cashPayment = Payment::create([
            'booking_id' => $this->booking->id,
            'attempt_number' => 1,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 200.00,
            'expected_amount' => 200.00,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_CASH,
            'transaction_reference' => 'CASH-' . uniqid('', true),
            'status' => Payment::STATUS_CASH_PENDING,
            'verification_status' => Payment::VERIFICATION_UNVERIFIED,
            'idempotency_key' => 'cash:other-branch',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/[Uu]nauthorized|branch/i');

        app(PaymentService::class)->confirmCashPayment($cashPayment, $otherManager);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 15. Cash payment is completely independent of Chapa TEST/LIVE mode
    // ─────────────────────────────────────────────────────────────────────────

    public function test_cash_payment_works_regardless_of_chapa_mode(): void
    {
        $manager = User::factory()->branchManager()->create(['branch_id' => $this->branch->id]);

        $vehicle = Vehicle::factory()->available()->create([
            'category_id' => Category::first()->id,
            'branch_id' => $this->branch->id,
            'rental_price_per_day' => 200,
        ]);

        foreach (['test', 'live'] as $mode) {
            Config::set('services.chapa.mode', $mode);
            // No Http::fake needed — cash must never call Chapa.
            Http::preventStrayRequests();

            // Use a fresh booking each iteration so payment_status starts clean.
            $freshBooking = Booking::factory()->create([
                'user_id' => $this->customer->id,
                'vehicle_id' => $vehicle->id,
                'branch_id' => $this->branch->id,
                'total_price' => 200,
                'subtotal' => 200,
                'additional_charges' => 0,
                'discount' => 0,
                'status' => Booking::STATUS_PAYMENT_REQUIRED,
                'payment_status' => Booking::PAYMENT_STATUS_PENDING,
                'branch_approval_status' => Booking::APPROVAL_APPROVED,
                'admin_approval_status' => Booking::APPROVAL_NOT_REQUIRED,
                'admin_approval_required' => false,
            ]);

            $cashPayment = app(PaymentService::class)->createCashPendingPayment(
                ['booking_id' => $freshBooking->id, 'payment_method' => Payment::METHOD_CASH],
                $this->customer->id
            );

            $this->assertEquals(Payment::STATUS_CASH_PENDING, $cashPayment->status);
            $this->assertEquals(Payment::METHOD_CASH, $cashPayment->payment_method);

            $confirmed = app(PaymentService::class)->confirmCashPayment($cashPayment, $manager);

            $this->assertEquals(Payment::STATUS_PAID, $confirmed->status);
            $this->assertEquals(Payment::VERIFICATION_MANUALLY_CONFIRMED, $confirmed->verification_status);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 16. Secret key is never exposed in API responses
    // ─────────────────────────────────────────────────────────────────────────

    public function test_secret_key_is_not_in_initialization_response(): void
    {
        $this->setChapaTestConfig();

        Http::fake([
            'api.chapa.co/v1/transaction/initialize' => Http::response([
                'status' => 'success',
                'data' => ['checkout_url' => 'https://checkout.chapa.co/pay'],
            ], 200),
        ]);

        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payments/initialize', [
                'booking_id' => $this->booking->id,
            ]);

        $response->assertStatus(201);
        $body = $response->getContent();

        $this->assertStringNotContainsString('CHASECK_TEST', $body);
        $this->assertStringNotContainsString('secret_key', $body);
        $this->assertStringNotContainsString('secret', strtolower($body));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function setChapaTestConfig(): void
    {
        Config::set('services.chapa', [
            'mode' => 'test',
            'secret_key' => 'CHASECK_TEST-abc123',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'http://localhost:8000/api/payments/callback',
            'return_url' => 'http://localhost:5173/payments/status',
            'webhook_url' => 'http://localhost:8000/api/payments/chapa/webhook',
            'webhook_secret' => 'CHASECK_TEST-abc123',
        ]);
    }

    private function makeProcessingPayment(string $txRef, float $amount): Payment
    {
        return Payment::create([
            'booking_id' => $this->booking->id,
            'attempt_number' => 1,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'amount' => $amount,
            'expected_amount' => $amount,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_ONLINE_PAYMENT,
            'gateway' => Payment::GATEWAY_CHAPA,
            'transaction_reference' => $txRef,
            'status' => Payment::STATUS_PROCESSING,
            'verification_status' => Payment::VERIFICATION_UNVERIFIED,
            'idempotency_key' => 'chapa:' . $this->booking->id . ':' . $txRef,
        ]);
    }
}
