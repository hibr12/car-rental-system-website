<?php

namespace Tests\Feature\Notifications;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingCreated;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentSuccess;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
        $this->paymentService = new PaymentService();
    }

    public function test_booking_created_notification_is_sent(): void
    {
        Notification::fake();

        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);

        $this->bookingService->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => now()->addDays(1)->format('Y-m-d 10:00:00'),
            'return_date' => now()->addDays(5)->format('Y-m-d 10:00:00'),
        ], $customer->id);

        Notification::assertSentTo($customer, BookingCreated::class);
    }

    public function test_booking_confirmed_notification_is_sent(): void
    {
        Notification::fake();

        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->pending()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->bookingService->confirmBooking($booking);

        Notification::assertSentTo($customer, BookingConfirmed::class);
    }

    public function test_booking_cancelled_notification_is_sent(): void
    {
        Notification::fake();

        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'reserved']);
        $booking = Booking::factory()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'confirmed',
        ]);

        $this->bookingService->cancelBooking($booking);

        Notification::assertSentTo($customer, BookingCancelled::class);
    }

    public function test_booking_rejected_notification_is_sent(): void
    {
        Notification::fake();

        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->pending()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->bookingService->rejectBooking($booking, 'Vehicle unavailable');

        Notification::assertSentTo($customer, BookingCancelled::class);
    }

    public function test_payment_success_notification_is_sent(): void
    {
        Notification::fake();

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

        $this->paymentService->processPayment([
            'booking_id' => $booking->id,
            'amount' => 400,
            'payment_method' => 'card',
        ], $customer->id);

        Notification::assertSentTo($customer, PaymentSuccess::class);
    }

    public function test_payment_failed_notification_is_sent(): void
    {
        Notification::fake();

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

        $this->paymentService->markAsFailed($payment);

        Notification::assertSentTo($customer, PaymentFailed::class);
    }

    public function test_notifications_are_stored_in_database(): void
    {
        $customer = User::factory()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = Booking::factory()->pending()->create([
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $booking->user->notify(new BookingConfirmed($booking));

        $this->assertDatabaseHas('notifications', [
            'type' => BookingConfirmed::class,
            'notifiable_id' => $customer->id,
            'notifiable_type' => User::class,
        ]);
    }
}
