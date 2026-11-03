<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDEX_NAME = 'driver_licenses_one_pending_per_user_unique';

    // PostgreSQL cannot build an index concurrently inside a transaction.
    public $withinTransaction = false;

    public function up(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            return;
        }

        $duplicateUsers = DB::table('driver_licenses')
            ->select('user_id')
            ->where('status', 'pending_review')
            ->whereNull('deleted_at')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUsers as $userId) {
            $submissions = DB::table('driver_licenses')
                ->where('user_id', $userId)
                ->where('status', 'pending_review')
                ->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN submitted_at IS NULL THEN 1 ELSE 0 END ASC')
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->get(['id']);

            $kept = $submissions->shift();

            if (! $kept || $submissions->isEmpty()) {
                continue;
            }

            DB::table('driver_licenses')
                ->whereIn('id', $submissions->pluck('id'))
                ->update([
                    'status' => 'replaced',
                    'replaced_by' => $kept->id,
                    'updated_at' => now(),
                ]);
        }

        $concurrently = $driver === 'pgsql' ? ' CONCURRENTLY' : '';

        // A cancelled concurrent build leaves an invalid index behind. Remove
        // only that invalid artifact so a deployment retry can rebuild it.
        if ($driver === 'pgsql') {
            $invalidIndex = DB::selectOne(
                'SELECT 1 AS invalid FROM pg_index WHERE indexrelid = to_regclass(?) AND NOT indisvalid',
                [self::INDEX_NAME],
            );

            if ($invalidIndex) {
                DB::statement(sprintf('DROP INDEX CONCURRENTLY IF EXISTS %s', self::INDEX_NAME));
            }
        }

        DB::statement(sprintf(
            "CREATE UNIQUE INDEX%s IF NOT EXISTS %s ON driver_licenses (user_id) WHERE status = 'pending_review' AND deleted_at IS NULL",
            $concurrently,
            self::INDEX_NAME,
        ));
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            return;
        }

        $concurrently = $driver === 'pgsql' ? ' CONCURRENTLY' : '';

        DB::statement(sprintf('DROP INDEX%s IF EXISTS %s', $concurrently, self::INDEX_NAME));
    }
};
