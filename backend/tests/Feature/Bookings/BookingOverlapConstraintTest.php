<?php

namespace Tests\Feature\Bookings;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingOverlapConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_postgresql_atomically_rejects_an_overlapping_blocking_booking(): void
    {
        $this->requirePostgreSql();

        $vehicle = Vehicle::factory()->available()->create();
        $pickup = now()->addDays(10)->startOfHour();
        $return = $pickup->copy()->addDays(3);

        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'user_id' => User::factory()->customer()->create()->id,
            'pickup_date' => $pickup,
            'return_date' => $return,
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $baseTransactionLevel = DB::transactionLevel();
        DB::beginTransaction();

        try {
            Booking::factory()->create([
                'vehicle_id' => $vehicle->id,
                'user_id' => User::factory()->customer()->create()->id,
                'pickup_date' => $pickup->copy()->addDay(),
                'return_date' => $return->copy()->addDay(),
                'status' => Booking::STATUS_PAYMENT_REQUIRED,
            ]);

            $this->fail('PostgreSQL should reject overlapping blocking bookings.');
        } catch (QueryException $e) {
            $this->assertSame('23P01', $e->errorInfo[0] ?? $e->getCode());
            $this->assertStringContainsString(
                'bookings_vehicle_dates_no_overlap',
                strtolower($e->getMessage()),
            );
        } finally {
            while (DB::transactionLevel() > $baseTransactionLevel) {
                DB::rollBack();
            }
        }

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_postgresql_allows_reuse_when_the_existing_booking_is_terminal(): void
    {
        $this->requirePostgreSql();

        $vehicle = Vehicle::factory()->available()->create();
        $pickup = now()->addDays(10)->startOfHour();
        $return = $pickup->copy()->addDays(3);

        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'pickup_date' => $pickup,
            'return_date' => $return,
            'status' => Booking::STATUS_COMPLETED,
        ]);
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'pickup_date' => $pickup,
            'return_date' => $return,
            'status' => Booking::STATUS_PAYMENT_REQUIRED,
        ]);

        $this->assertDatabaseCount('bookings', 2);
    }

    private function requirePostgreSql(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL exclusion constraint integration test.');
        }
    }
}
