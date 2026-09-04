<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'gateway_reference')) {
                $table->string('gateway_reference')->nullable()->after('transaction_reference');
            }
            if (!Schema::hasColumn('payments', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('payments', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('verified_at');
            }
            if (!Schema::hasColumn('payments', 'verification_source')) {
                $table->string('verification_source')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('payments', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('verification_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['gateway_reference', 'verified_at', 'verified_by', 'verification_source', 'failure_reason'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    if ($col === 'verified_by') {
                        $table->dropConstrainedForeignId('verified_by');
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
