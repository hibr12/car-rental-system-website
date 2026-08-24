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
        $isSqlite = in_array(DB::connection()->getDriverName(), ['sqlite'], true);

        // SQLite cannot rebuild a table while an index still references a
        // dropped column — remove it explicitly first.
        DB::statement('DROP INDEX IF EXISTS branches_status_index');

        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('branches', 'code')) {
                $table->string('code')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('branches', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('email');
            }
            if (!Schema::hasColumn('branches', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('branches', 'opening_time')) {
                $table->time('opening_time')->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('branches', 'closing_time')) {
                $table->time('closing_time')->nullable()->after('opening_time');
            }
        });

        // Change status from string to enum: drop, then re-create.
        // Two separate passes keep SQLite's implicit table rebuild valid.
        if (Schema::hasColumn('branches', 'status')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            Schema::table('branches', function (Blueprint $table) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('manager_id');
            });
        }

        // Generate codes for existing branches
        $branches = DB::table('branches')->whereNull('code')->get();
        foreach ($branches as $branch) {
            DB::table('branches')
                ->where('id', $branch->id)
                ->update(['code' => strtoupper(substr($branch->name, 0, 3)) . '-' . $branch->id]);
        }

        // SET NOT NULL is Postgres/MySQL syntax and not supported by SQLite;
        // the unique index plus application-level guarantees cover sqlite dev.
        if (!$isSqlite) {
            DB::statement('ALTER TABLE branches ALTER COLUMN code SET NOT NULL');
        }
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
