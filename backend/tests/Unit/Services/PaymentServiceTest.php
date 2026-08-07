<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService();
    }

    public function test_process_payment_successfully(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 400,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        $payment = $this->service->processPayment([
            'booking_id' => $booking->id,
            'amount' => 400,
            'payment_method' => 'card',
        ], $customer->id);

        $this->assertNotNull($payment->id);
        $this->assertEquals(400, $payment->amount);
        $this->assertEquals('card', $payment->payment_method);
        $this->assertEquals('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->transaction_reference);
        $this->assertStringStartsWith('TXN-', $payment->transaction_reference);

        $booking->refresh();
        $this->assertEquals('paid', $booking->payment_status);
    }

    public function test_process_payment_rejects_unauthorized_user(): void
    {
        $customer = User::factory()->customer()->create();
        $otherUser = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 400,
            'status' => 'confirmed',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized payment for this booking.');

        $this->service->processPayment([
            'booking_id' => $booking->id,
            'amount' => 400,
            'payment_method' => 'card',
        ], $otherUser->id);
    }

    public function test_process_payment_rejects_amount_mismatch(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 400,
            'status' => 'confirmed',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amount must match the booking total.');

        $this->service->processPayment([
            'booking_id' => $booking->id,
            'amount' => 100,
            'payment_method' => 'card',
        ], $customer->id);
    }

    public function test_process_payment_rejects_already_paid_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 400,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Booking is already paid.');

        $this->service->processPayment([
            'booking_id' => $booking->id,
            'amount' => 400,
            'payment_method' => 'card',
        ], $customer->id);
    }

    public function test_refund_payment_successfully(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 400,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $payment = Payment::factory()->paid()->create([
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
            'amount' => 400,
        ]);

        $result = $this->service->refundPayment($payment);

        $this->assertEquals('refunded', $result->status);
        $booking->refresh();
        $this->assertEquals('refunded', $booking->payment_status);
    }

    public function test_refund_payment_rejects_non_paid(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 400,
            'status' => 'confirmed',
        ]);
        $payment = Payment::factory()->pending()->create([
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only paid payments can be refunded.');

        $this->service->refundPayment($payment);
    }

    public function test_mark_as_failed_updates_booking_status(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 400,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::factory()->pending()->create([
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
        ]);

        $result = $this->service->markAsFailed($payment);

        $this->assertEquals('failed', $result->status);
        $booking->refresh();
        $this->assertEquals('failed', $booking->payment_status);
    }

    public function test_transaction_reference_format(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total_price' => 400,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        $payment = $this->service->processPayment([
            'booking_id' => $booking->id,
            'amount' => 400,
            'payment_method' => 'card',
        ], $customer->id);

        $this->assertMatchesRegularExpression('/^TXN-\d{8}-[A-Z0-9]{8}$/', $payment->transaction_reference);
    }
}
