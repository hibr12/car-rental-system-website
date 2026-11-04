<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Production's proxied PostgreSQL connection cannot reliably enter an
     * application-managed transaction. This migration also installs an
     * extension, so each statement must run in PostgreSQL's autocommit mode.
     */
    public $withinTransaction = false;

    private const CONSTRAINT = 'bookings_vehicle_dates_no_overlap';

    private const BLOCKING_STATUSES_SQL = "
        'pending_payment',
        'payment_required',
        'payment_processing',
        'payment_verified',
        'pending_branch_approval',
        'pending_admin_approval',
        'confirmed',
        'ready_for_pickup',
        'active',
        'return_pending',
        'pending',
        'branch_review'
    ";

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        $existingConstraint = DB::selectOne(
            "SELECT 1 FROM pg_constraint WHERE conname = ? AND conrelid = 'bookings'::regclass LIMIT 1",
            [self::CONSTRAINT],
        );

        if ($existingConstraint !== null) {
            return;
        }

        $invalidDateCount = DB::table('bookings')
            ->whereColumn('pickup_date', '>=', 'return_date')
            ->count();

        if ($invalidDateCount > 0) {
            throw new RuntimeException(
                "Cannot protect booking availability: {$invalidDateCount} booking(s) have an invalid date interval.",
            );
        }

        $blockingStatuses = self::BLOCKING_STATUSES_SQL;
        $overlap = DB::selectOne(<<<SQL
            SELECT 1
            FROM bookings first_booking
            JOIN bookings second_booking
              ON first_booking.id < second_booking.id
             AND first_booking.vehicle_id = second_booking.vehicle_id
             AND first_booking.pickup_date <= second_booking.return_date
             AND first_booking.return_date >= second_booking.pickup_date
            WHERE first_booking.status IN ({$blockingStatuses})
              AND second_booking.status IN ({$blockingStatuses})
            LIMIT 1
            SQL);

        if ($overlap !== null) {
            throw new RuntimeException(
                'Cannot protect booking availability: overlapping active bookings already exist.',
            );
        }

        DB::statement(<<<SQL
            ALTER TABLE bookings
            ADD CONSTRAINT bookings_vehicle_dates_no_overlap
            EXCLUDE USING gist (
                vehicle_id WITH =,
                tsrange(pickup_date, return_date, '[]') WITH &&
            )
            WHERE (status IN ({$blockingStatuses}))
            SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_vehicle_dates_no_overlap',
        );
    }
};
