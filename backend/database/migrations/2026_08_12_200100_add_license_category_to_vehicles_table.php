<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'required_license_category')) {
                // null = no specific license category required for this vehicle.
                // 'automobile' = standard car license (most common).
                // Values mirror DriverLicense::CATEGORIES.
                $table->string('required_license_category', 50)
                    ->nullable()
                    ->after('status')
                    ->comment('Required customer license category. null = no requirement.');
            }

            if (!Schema::hasColumn('vehicles', 'requires_license')) {
                $table->boolean('requires_license')
                    ->default(true)
                    ->after('required_license_category')
                    ->comment('Whether a verified driver\'s license is required to book this vehicle.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['required_license_category', 'requires_license']);
        });
    }
};
