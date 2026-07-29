<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BookingService();
    }

    public function test_create_booking_successfully(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'rental_price_per_day' => 100,
        ]);

        $booking = $this->service->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00'),
        ], $customer->id);

        $this->assertNotNull($booking->booking_reference);
        $this->assertStringStartsWith('BK-', $booking->booking_reference);
        $this->assertEquals($customer->id, $booking->user_id);
        $this->assertEquals($vehicle->id, $booking->vehicle_id);
        $this->assertEquals('pending', $booking->status);
        $this->assertEquals('unpaid', $booking->payment_status);
        $this->assertEquals(4, $booking->number_of_days);
        $this->assertEquals(400, $booking->total_price);
    }

    public function test_create_booking_calculates_price_correctly(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'rental_price_per_day' => 75.50,
        ]);

        $booking = $this->service->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00'),
        ], $customer->id);

        $this->assertEquals(3, $booking->number_of_days);
        $this->assertEquals(75.50, $booking->price_per_day);
        $this->assertEquals(226.50, $booking->subtotal);
        $this->assertEquals(226.50, $booking->total_price);
    }

    public function test_create_booking_rejects_unavailable_vehicle(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'category_id' => $category->id,
            'status' => 'maintenance',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle is not available for booking.');

        $this->service->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00'),
        ], $customer->id);
    }

    public function test_create_booking_rejects_overlapping_dates(): void
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);

        $this->service->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->addDays(2)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(6)->format('Y-m-d 10:00:00'),
        ], $otherCustomer->id);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vehicle is already booked for the selected dates.');

        $this->service->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00'),
        ], $customer->id);
    }

    public function test_create_booking_rejects_return_before_pickup(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Return date must be after pickup date.');

        $this->service->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(2)->format('Y-m-d 10:00:00'),
        ], $customer->id);
    }

    public function test_confirm_booking_successfully(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = \App\Models\Booking::factory()->pending()->create([
            'vehicle_id' => $vehicle->id,
        ]);

        $result = $this->service->confirmBooking($booking);

        $this->assertEquals('confirmed', $result->status);
        $this->assertEquals('pending', $result->payment_status);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'reserved']);
    }

    public function test_confirm_booking_rejects_non_pending(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = \App\Models\Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'confirmed',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only pending bookings can be confirmed.');

        $this->service->confirmBooking($booking);
    }

    public function test_reject_booking_successfully(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = \App\Models\Booking::factory()->pending()->create([
            'vehicle_id' => $vehicle->id,
        ]);

        $result = $this->service->rejectBooking($booking, 'Vehicle needs service');

        $this->assertEquals('rejected', $result->status);
        $this->assertStringContainsString('Vehicle needs service', $result->notes);
    }

    public function test_cancel_booking_releases_vehicle(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'reserved']);
        $booking = \App\Models\Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'confirmed',
        ]);

        $result = $this->service->cancelBooking($booking);

        $this->assertEquals('cancelled', $result->status);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'available']);
    }

    public function test_cancel_booking_rejects_active_booking(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = \App\Models\Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only pending or confirmed bookings can be cancelled.');

        $this->service->cancelBooking($booking);
    }

    public function test_mark_as_picked_up_sets_vehicle_rented(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'reserved']);
        $booking = \App\Models\Booking::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
        ]);

        $result = $this->service->markAsPickedUp($booking);

        $this->assertEquals('active', $result->status);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'rented']);
    }

    public function test_mark_as_picked_up_rejects_non_confirmed(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = \App\Models\Booking::factory()->pending()->create([
            'vehicle_id' => $vehicle->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only confirmed bookings can be marked as picked up.');

        $this->service->markAsPickedUp($booking);
    }

    public function test_mark_as_returned_completes_booking(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'rented']);
        $booking = \App\Models\Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);

        $result = $this->service->markAsReturned($booking);

        $this->assertEquals('completed', $result->status);
        $this->assertEquals('paid', $result->payment_status);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'available']);
    }

    public function test_mark_as_returned_rejects_non_active(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $booking = \App\Models\Booking::factory()->confirmed()->create([
            'vehicle_id' => $vehicle->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only active rentals can be returned.');

        $this->service->markAsReturned($booking);
    }

    public function test_has_overlap_detects_conflicting_bookings(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $customer = User::factory()->customer()->create();

        $this->service->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->addDays(2)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(6)->format('Y-m-d 10:00:00'),
        ], $customer->id);

        $hasOverlap = $this->service->hasOverlap(
            $vehicle->id,
            Carbon::tomorrow()->addDays(3),
            Carbon::tomorrow()->addDays(5)
        );

        $this->assertTrue($hasOverlap);
    }

    public function test_has_overlap_returns_false_for_no_conflict(): void
    {
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);

        $hasOverlap = $this->service->hasOverlap(
            $vehicle->id,
            Carbon::tomorrow()->addDays(10),
            Carbon::tomorrow()->addDays(15)
        );

        $this->assertFalse($hasOverlap);
    }

    public function test_booking_reference_format(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);

        $booking = $this->service->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00'),
        ], $customer->id);

        $this->assertMatchesRegularExpression('/^BK-\d{8}-[A-Z0-9]{6}$/', $booking->booking_reference);
    }

    public function test_create_booking_with_discount_and_charges(): void
    {
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'rental_price_per_day' => 100,
        ]);

        $booking = $this->service->createBooking([
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Main Office',
            'return_location' => 'Main Office',
            'pickup_date' => Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00'),
            'return_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d 10:00:00'),
            'additional_charges' => 50,
            'discount' => 25,
        ], $customer->id);

        $this->assertEquals(50, $booking->additional_charges);
        $this->assertEquals(25, $booking->discount);
        $this->assertEquals(425, $booking->total_price);
    }
}
