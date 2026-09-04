<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
            $table->string('license_number')->nullable()->after('branch_id');
            $table->string('license_image')->nullable()->after('license_number');
            $table->string('license_status')->default('not_uploaded')->after('license_image');
            $table->timestamp('license_verified_at')->nullable()->after('license_status');
            $table->index('branch_id');
            $table->index('license_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['license_status']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'license_number', 'license_image', 'license_status', 'license_verified_at']);
        });
    }
};
