<?php

namespace Tests\Feature\Bookings;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\Support\HasVerifiedLicense;
use Tests\TestCase;

class BookingCreationFailureTest extends TestCase
{
    use HasVerifiedLicense, RefreshDatabase;

    public function test_unexpected_creation_failure_returns_a_safe_traceable_error(): void
    {
        $customer = User::factory()->customer()->create();
        $this->giveVerifiedLicense($customer);
        $vehicle = Vehicle::factory()->available()->create();

        $service = Mockery::mock(BookingService::class);
        $service->shouldReceive('createBooking')
            ->once()
            ->andThrow(new RuntimeException('Synthetic database credential detail.'));
        $this->app->instance(BookingService::class, $service);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/bookings', [
                'vehicle_id' => $vehicle->id,
                'pickup_location' => 'Airport',
                'return_location' => 'Airport',
                'pickup_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'return_date' => now()->addDays(4)->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error_code' => 'BOOKING_CREATION_FAILED',
            ])
            ->assertJsonStructure(['message', 'error_id']);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->json('error_id'),
        );
        $this->assertStringNotContainsString('credential detail', $response->getContent());
        $this->assertDatabaseCount('bookings', 0);
    }
}
