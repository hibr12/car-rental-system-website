<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('booking_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });

        DB::table('reviews')
            ->whereNull('branch_id')
            ->orderBy('id')
            ->chunkById(100, function ($reviews) {
                foreach ($reviews as $review) {
                    $branchId = DB::table('bookings')
                        ->where('id', $review->booking_id)
                        ->value('branch_id');

                    if ($branchId) {
                        DB::table('reviews')
                            ->where('id', $review->id)
                            ->update(['branch_id' => $branchId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
