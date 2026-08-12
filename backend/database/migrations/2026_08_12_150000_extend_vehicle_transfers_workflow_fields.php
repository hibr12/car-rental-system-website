<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // We currently store status as a VARCHAR with a check constraint.
        // Extend it to the full workflow: pending → approved → in_transit → completed
        // plus terminal: rejected, cancelled.
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vehicle_transfers DROP CONSTRAINT IF EXISTS vehicle_transfers_status_check');
        }

        // Backfill new status value for legacy rows.
        DB::statement("UPDATE vehicle_transfers SET status = 'pending' WHERE status = 'requested'");

        Schema::table('vehicle_transfers', function (Blueprint $table) {
            // Workflow timestamps
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Actor fields
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();

            // Terminal reasons
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
        });

        // Recreate the status check constraint with the extended set (Postgres only).
        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE vehicle_transfers
                ADD CONSTRAINT vehicle_transfers_status_check
                CHECK (status IN (
                    'pending',
                    'requested',
                    'approved',
                    'in_transit',
                    'completed',
                    'rejected',
                    'cancelled'
                ))
            ");

            // Update default to pending (instead of requested).
            DB::statement("ALTER TABLE vehicle_transfers ALTER COLUMN status SET DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        // Best-effort rollback (not expected to be used in production).
        Schema::table('vehicle_transfers', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['started_by']);

            $table->dropColumn([
                'requested_at',
                'approved_at',
                'in_transit_at',
                'completed_at',
                'completed_by',
                'rejected_at',
                'rejected_by',
                'rejection_reason',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
                'started_by',
            ]);
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vehicle_transfers DROP CONSTRAINT IF EXISTS vehicle_transfers_status_check');

            // Restore the older constraint shape.
            DB::statement("
                ALTER TABLE vehicle_transfers
                ADD CONSTRAINT vehicle_transfers_status_check
                CHECK (status IN ('requested', 'approved', 'in_transit', 'completed', 'cancelled'))
            ");
        }
    }
};

