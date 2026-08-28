<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('notes');
            }
            if (!Schema::hasColumn('bookings', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }
            if (!Schema::hasColumn('bookings', 'archived_by')) {
                $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete()->after('archived_at');
            }
            if (!Schema::hasColumn('bookings', 'archive_reason')) {
                $table->string('archive_reason')->nullable()->after('archived_by');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('confirmed_at');
            }
            if (!Schema::hasColumn('payments', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('is_archived');
            }
            if (!Schema::hasColumn('payments', 'archived_by')) {
                $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete()->after('archived_at');
            }
            if (!Schema::hasColumn('payments', 'archive_reason')) {
                $table->string('archive_reason')->nullable()->after('archived_by');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['is_archived', 'status', 'created_at'], 'bookings_archive_status_created_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['is_archived', 'status', 'created_at'], 'payments_archive_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_archive_status_created_idx');
            if (Schema::hasColumn('bookings', 'archived_by')) {
                $table->dropConstrainedForeignId('archived_by');
            }
            foreach (['is_archived', 'archived_at', 'archive_reason'] as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_archive_status_created_idx');
            if (Schema::hasColumn('payments', 'archived_by')) {
                $table->dropConstrainedForeignId('archived_by');
            }
            foreach (['is_archived', 'archived_at', 'archive_reason'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
