<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('code')->nullable()->unique()->after('name');
            $table->decimal('latitude', 10, 8)->nullable()->after('email');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->time('opening_time')->nullable()->after('longitude');
            $table->time('closing_time')->nullable()->after('opening_time');
            
            // Change status from string to enum
            $table->dropColumn('status');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('manager_id');
        });
        
        // Generate codes for existing branches
        $branches = DB::table('branches')->whereNull('code')->get();
        foreach ($branches as $branch) {
            DB::table('branches')
                ->where('id', $branch->id)
                ->update(['code' => strtoupper(substr($branch->name, 0, 3)) . '-' . $branch->id]);
        }
        
        // Now make code non-nullable
        DB::statement('ALTER TABLE branches ALTER COLUMN code SET NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'code', 'latitude', 'longitude', 'opening_time', 'closing_time']);
            $table->dropColumn('status');
            $table->string('status')->default('active')->after('manager_id');
        });
    }
};
