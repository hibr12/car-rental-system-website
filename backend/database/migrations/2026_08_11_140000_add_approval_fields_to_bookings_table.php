<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'branch_approval_status')) {
                $table->string('branch_approval_status')->default('pending')->after('status');
            }
            if (!Schema::hasColumn('bookings', 'admin_approval_status')) {
                $table->string('admin_approval_status')->default('pending')->after('branch_approval_status');
            }
            if (!Schema::hasColumn('bookings', 'branch_approved_at')) {
                $table->timestamp('branch_approved_at')->nullable()->after('admin_approval_status');
            }
            if (!Schema::hasColumn('bookings', 'branch_approved_by')) {
                $table->foreignId('branch_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('branch_approved_at');
            }
            if (!Schema::hasColumn('bookings', 'admin_approved_at')) {
                $table->timestamp('admin_approved_at')->nullable()->after('branch_approved_by');
            }
            if (!Schema::hasColumn('bookings', 'admin_approved_by')) {
                $table->foreignId('admin_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('admin_approved_at');
            }
            if (!Schema::hasColumn('bookings', 'rejected_by_role')) {
                $table->string('rejected_by_role')->nullable()->after('admin_approved_by');
            }
            if (!Schema::hasColumn('bookings', 'rejected_by_user_id')) {
                $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('rejected_by_role');
            }
            if (!Schema::hasColumn('bookings', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');
            }
            if (!Schema::hasColumn('bookings', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'branch_approval_status', 'admin_approval_status',
                'branch_approved_at', 'branch_approved_by',
                'admin_approved_at', 'admin_approved_by',
                'rejected_by_role', 'rejected_by_user_id',
                'rejected_at', 'rejection_reason',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    if (in_array($col, ['branch_approved_by', 'admin_approved_by', 'rejected_by_user_id'])) {
                        $table->dropForeign(['bookings_' . $col . '_foreign']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
