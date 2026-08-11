<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'verification_status')) {
                $table->string('verification_status')->default('unverified')->after('status');
            }
            if (!Schema::hasColumn('payments', 'gateway')) {
                $table->string('gateway')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('payments', 'gateway_status')) {
                $table->string('gateway_status')->nullable()->after('gateway_reference');
            }
            if (!Schema::hasColumn('payments', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('gateway_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['verification_status', 'gateway', 'gateway_status', 'gateway_response'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
