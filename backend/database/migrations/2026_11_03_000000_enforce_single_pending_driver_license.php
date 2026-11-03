<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDEX_NAME = 'driver_licenses_one_pending_per_user_unique';

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
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

        DB::statement(sprintf(
            "CREATE UNIQUE INDEX %s ON driver_licenses (user_id) WHERE status = 'pending_review' AND deleted_at IS NULL",
            self::INDEX_NAME,
        ));
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            return;
        }

        DB::statement(sprintf('DROP INDEX IF EXISTS %s', self::INDEX_NAME));
    }
};
