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

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vehicle_transfers DROP CONSTRAINT IF EXISTS vehicle_transfers_status_check');
        }

        Schema::table('vehicle_transfers', function (Blueprint $table) {
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->foreignId('failed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('source_odometer')->nullable();
            $table->unsignedInteger('destination_odometer')->nullable();
            $table->unsignedTinyInteger('source_fuel_level')->nullable();
            $table->unsignedTinyInteger('destination_fuel_level')->nullable();
            $table->string('source_condition', 50)->nullable();
            $table->string('destination_condition', 50)->nullable();

            $table->text('request_notes')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('release_notes')->nullable();
            $table->text('receiving_notes')->nullable();
            $table->text('damage_report')->nullable();
            $table->text('failure_reason')->nullable();
        });

        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE vehicle_transfers
                ADD CONSTRAINT vehicle_transfers_status_check
                CHECK (status IN (
                    'pending',
                    'requested',
                    'approved',
                    'ready_for_release',
                    'in_transit',
                    'received',
                    'received_pending_inspection',
                    'completed',
                    'rejected',
                    'cancelled',
                    'failed'
                ))
            ");
        }

        // Backfill note fields from legacy column where present.
        DB::table('vehicle_transfers')
            ->whereNull('request_notes')
            ->whereNotNull('notes')
            ->update(['request_notes' => DB::raw('notes')]);
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vehicle_transfers DROP CONSTRAINT IF EXISTS vehicle_transfers_status_check');
        }

        Schema::table('vehicle_transfers', function (Blueprint $table) {
            $table->dropForeign(['released_by']);
            $table->dropForeign(['received_by']);
            $table->dropForeign(['failed_by']);

            $table->dropColumn([
                'released_by',
                'received_by',
                'released_at',
                'received_at',
                'failed_at',
                'failed_by',
                'source_odometer',
                'destination_odometer',
                'source_fuel_level',
                'destination_fuel_level',
                'source_condition',
                'destination_condition',
                'request_notes',
                'approval_notes',
                'release_notes',
                'receiving_notes',
                'damage_report',
                'failure_reason',
            ]);
        });

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
        }
    }
};
