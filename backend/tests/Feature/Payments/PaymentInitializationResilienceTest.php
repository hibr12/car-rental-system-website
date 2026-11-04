<?php

namespace Tests\Feature\Payments;

use App\Events\PaymentCreated;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PaymentInitializationResilienceTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.chapa', [
            'mode' => 'test',
            'secret_key' => 'CHASECK_TEST-server-only',
            'base_url' => 'https://api.chapa.co',
            'callback_url' => 'https://backend.example/api/payments/callback',
            'return_url' => 'https://frontend.example/payments/status',
            'webhook_url' => 'https://backend.example/api/payments/chapa/webhook',
            'webhook_secret' => 'CHASECK_TEST-server-only',
        ]);

        $branch = Branch::factory()->create(['code' => 'PAY-RES']);
        $category = Category::factory()->create();
        $this->customer = User::factory()->customer()->create([
            'name' => 'Payment Customer',
            'email' => 'payment-customer@example.test',
        ]);
        $vehicle = Vehicle::factory()->available()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
        ]);
        $this->booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'branch_id' => $branch->id,
            'subtotal' => 500,
            'additional_charges' => 0,
            'discount' => 0,
            'total_price' => 500,
            'status' => Booking::STATUS_PAYMENT_REQUIRED,
            'payment_status' => Booking::PAYMENT_STATUS_PENDING,
            'branch_approval_status' => Booking::APPROVAL_NOT_REQUIRED,
            'admin_approval_status' => Booking::APPROVAL_NOT_REQUIRED,
            'admin_approval_required' => false,
        ]);
    }

    public function test_initialization_commits_without_an_explicit_service_transaction(): void
    {
        Http::fake([
            'api.chapa.co/v1/transaction/initialize' => Http::response([
                'status' => 'success',
                'data' => ['checkout_url' => 'https://checkout.chapa.co/pay/resilient'],
            ]),
        ]);

        $baselineLevel = DB::transactionLevel();
        $eventTransactionLevel = null;
        Event::listen(PaymentCreated::class, function () use (&$eventTransactionLevel): void {
            $eventTransactionLevel = DB::transactionLevel();
        });

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/payments/initialize', ['booking_id' => $this->booking->id]);

        $response->assertCreated()
            ->assertJsonPath('data.checkout_url', 'https://checkout.chapa.co/pay/resilient');
        $this->assertSame($baselineLevel, $eventTransactionLevel);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'status' => Payment::STATUS_PROCESSING,
            'currency' => 'ETB',
        ]);
    }

    public function test_exact_retry_reuses_the_stored_checkout_without_calling_chapa_twice(): void
    {
        Http::fake([
            'api.chapa.co/v1/transaction/initialize' => Http::response([
                'status' => 'success',
                'data' => ['checkout_url' => 'https://checkout.chapa.co/pay/reused'],
            ]),
        ]);

        $first = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/payments/initialize', ['booking_id' => $this->booking->id]);
        $second = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/payments/initialize', ['booking_id' => $this->booking->id]);

        $first->assertCreated();
        $second->assertOk()
            ->assertJsonPath('message', 'Your existing Chapa checkout is ready.');
        $this->assertSame($first->json('data.tx_ref'), $second->json('data.tx_ref'));
        $this->assertSame($first->json('data.checkout_url'), $second->json('data.checkout_url'));
        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(1);
    }

    public function test_committed_intent_survives_a_synchronous_event_failure(): void
    {
        Http::fake([
            'api.chapa.co/v1/transaction/initialize' => Http::response([
                'status' => 'success',
                'data' => ['checkout_url' => 'https://checkout.chapa.co/pay/event-safe'],
            ]),
        ]);
        Event::listen(PaymentCreated::class, static function (): void {
            throw new RuntimeException('Synthetic listener failure.');
        });

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/payments/initialize', ['booking_id' => $this->booking->id])
            ->assertCreated();

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'status' => Payment::STATUS_PROCESSING,
        ]);
    }

    public function test_gateway_failure_returns_a_traceable_error_and_releases_the_live_intent(): void
    {
        Http::fake([
            'api.chapa.co/v1/transaction/initialize' => Http::response([
                'status' => 'failed',
                'message' => 'Gateway temporarily unavailable',
            ], 500),
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/payments/initialize', ['booking_id' => $this->booking->id]);

        $response->assertStatus(502)
            ->assertJsonPath('error_code', 'PAYMENT_INITIALIZATION_FAILED')
            ->assertJsonStructure(['message', 'error_id']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}$/i',
            (string) $response->json('error_id'),
        );
        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'status' => Payment::STATUS_FAILED,
        ]);
    }

    public function test_non_json_gateway_failure_is_traceable_and_releases_the_live_intent(): void
    {
        Http::fake([
            'api.chapa.co/v1/transaction/initialize' => Http::response(
                '<html><body>Bad gateway</body></html>',
                502,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/payments/initialize', ['booking_id' => $this->booking->id]);

        $response->assertStatus(502)
            ->assertJsonPath('error_code', 'PAYMENT_INITIALIZATION_FAILED')
            ->assertJsonStructure(['message', 'error_id']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f-]{36}$/i',
            (string) $response->json('error_id'),
        );
        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'status' => Payment::STATUS_FAILED,
        ]);
    }
}
