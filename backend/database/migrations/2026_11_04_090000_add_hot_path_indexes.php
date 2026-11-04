<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** PostgreSQL cannot create or drop concurrent indexes in a transaction. */
    public $withinTransaction = false;

    /** @var array<string, string> */
    private const INDEXES = [
        'notifications_recipient_created_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS notifications_recipient_created_idx '
            . 'ON notifications (notifiable_type, notifiable_id, created_at DESC)',
        'notifications_unread_recipient_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS notifications_unread_recipient_idx '
            . 'ON notifications (notifiable_type, notifiable_id) WHERE read_at IS NULL',
        'driver_licenses_review_queue_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS driver_licenses_review_queue_idx '
            . 'ON driver_licenses (status, submitted_at DESC) WHERE deleted_at IS NULL',
        'bookings_user_created_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS bookings_user_created_idx '
            . 'ON bookings (user_id, created_at DESC)',
        'bookings_active_created_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS bookings_active_created_idx '
            . 'ON bookings (created_at DESC) WHERE is_archived = false',
        'bookings_active_branch_created_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS bookings_active_branch_created_idx '
            . 'ON bookings (branch_id, created_at DESC) WHERE is_archived = false',
        'payments_active_user_created_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS payments_active_user_created_idx '
            . 'ON payments (user_id, created_at DESC) WHERE is_archived = false',
        'payments_active_branch_created_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS payments_active_branch_created_idx '
            . 'ON payments (branch_id, created_at DESC) WHERE is_archived = false',
        'payments_booking_created_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS payments_booking_created_idx '
            . 'ON payments (booking_id, created_at DESC)',
        'payments_paid_branch_date_idx' =>
            "CREATE INDEX CONCURRENTLY IF NOT EXISTS payments_paid_branch_date_idx "
            . "ON payments (branch_id, paid_at) WHERE status = 'paid'",
        'payments_gateway_reference_idx' =>
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS payments_gateway_reference_idx '
            . 'ON payments (gateway_reference) WHERE gateway_reference IS NOT NULL',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::INDEXES as $name => $statement) {
            $this->dropInvalidIndex($name);
            DB::statement($statement);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_reverse(array_keys(self::INDEXES)) as $name) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$name}");
        }
    }

    private function dropInvalidIndex(string $name): void
    {
        $index = DB::selectOne(<<<SQL
            SELECT pg_index.indisvalid
            FROM pg_index
            JOIN pg_class ON pg_class.oid = pg_index.indexrelid
            JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
            WHERE pg_namespace.nspname = current_schema()
              AND pg_class.relname = ?
            SQL, [$name]);

        if ($index !== null && ! (bool) $index->indisvalid) {
            // Names are constants above, not request data.
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$name}");
        }
    }
};
