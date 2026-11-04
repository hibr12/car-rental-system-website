<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** PostgreSQL cannot build indexes concurrently inside a transaction. */
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // A cancelled CREATE INDEX CONCURRENTLY can leave an index with the
        // intended name but indisvalid=false. IF NOT EXISTS would otherwise
        // skip it on the next deployment and leave the payment guard inactive.
        $this->dropInvalidIndex('payments_one_live_chapa_per_booking');
        $this->dropInvalidIndex('payments_one_settled_per_booking');

        $liveDuplicate = DB::selectOne(<<<'SQL'
            SELECT booking_id
            FROM payments
            WHERE payment_method = 'online_payment'
              AND status IN ('pending', 'processing')
              AND is_archived = false
            GROUP BY booking_id
            HAVING COUNT(*) > 1
            LIMIT 1
            SQL);

        if ($liveDuplicate !== null) {
            throw new RuntimeException(
                'Cannot protect Chapa initialization: a booking has multiple live online payment intents.',
            );
        }

        $settledDuplicate = DB::selectOne(<<<'SQL'
            SELECT booking_id
            FROM payments
            WHERE status = 'paid'
              AND verification_status IN ('verified', 'manually_confirmed')
            GROUP BY booking_id
            HAVING COUNT(*) > 1
            LIMIT 1
            SQL);

        if ($settledDuplicate !== null) {
            throw new RuntimeException(
                'Cannot protect payment settlement: a booking has multiple verified paid payments.',
            );
        }

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX CONCURRENTLY IF NOT EXISTS payments_one_live_chapa_per_booking
            ON payments (booking_id)
            WHERE payment_method = 'online_payment'
              AND status IN ('pending', 'processing')
              AND is_archived = false
            SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX CONCURRENTLY IF NOT EXISTS payments_one_settled_per_booking
            ON payments (booking_id)
            WHERE status = 'paid'
              AND verification_status IN ('verified', 'manually_confirmed')
            SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS payments_one_live_chapa_per_booking');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS payments_one_settled_per_booking');
    }

    private function dropInvalidIndex(string $name): void
    {
        $index = DB::selectOne(<<<'SQL'
            SELECT pg_index.indisvalid
            FROM pg_index
            JOIN pg_class ON pg_class.oid = pg_index.indexrelid
            JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
            WHERE pg_namespace.nspname = current_schema()
              AND pg_class.relname = ?
            SQL, [$name]);

        if ($index !== null && ! (bool) $index->indisvalid) {
            // Index names are constants supplied by this migration.
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$name}");
        }
    }
};
