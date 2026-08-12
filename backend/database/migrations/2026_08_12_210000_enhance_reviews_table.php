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
            $table->unsignedTinyInteger('overall_rating')->nullable()->after('branch_id');
        });

        if (Schema::hasColumn('reviews', 'rating')) {
            DB::table('reviews')->update([
                'overall_rating' => DB::raw('rating'),
            ]);

            Schema::table('reviews', function (Blueprint $table) {
                $table->dropIndex(['rating']);
            });

            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('rating');
            });
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('vehicle_rating')->nullable()->after('overall_rating');
            $table->unsignedTinyInteger('cleanliness_rating')->nullable()->after('vehicle_rating');
            $table->unsignedTinyInteger('staff_rating')->nullable()->after('cleanliness_rating');
            $table->unsignedTinyInteger('value_rating')->nullable()->after('staff_rating');
            $table->text('admin_response')->nullable()->after('comment');
            $table->timestamp('admin_response_at')->nullable()->after('admin_response');
            $table->foreignId('admin_response_by')->nullable()->after('admin_response_at')
                ->constrained('users')->nullOnDelete();
            $table->index('status');
            $table->index('created_at');
            $table->index('overall_rating');
        });

        DB::table('reviews')->orderBy('id')->chunkById(100, function ($reviews) {
            foreach ($reviews as $review) {
                DB::table('reviews')->where('id', $review->id)->update([
                    'vehicle_rating' => $review->overall_rating,
                    'cleanliness_rating' => $review->overall_rating,
                    'staff_rating' => $review->overall_rating,
                    'value_rating' => $review->overall_rating,
                ]);
            }
        });

        DB::table('reviews')->whereIn('status', ['approved', 'pending'])->update(['status' => 'published']);
        DB::table('reviews')->where('status', 'rejected')->update(['status' => 'hidden']);

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('review_reminder_sent_at')->nullable()->after('returned_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('review_reminder_sent_at');
        });

        DB::table('reviews')->where('status', 'published')->update(['status' => 'approved']);
        DB::table('reviews')->where('status', 'hidden')->update(['status' => 'rejected']);

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_response_by');
            $table->dropIndex(['overall_rating']);
            $table->dropColumn([
                'vehicle_rating',
                'cleanliness_rating',
                'staff_rating',
                'value_rating',
                'admin_response',
                'admin_response_at',
            ]);
            $table->unsignedTinyInteger('rating')->nullable()->after('branch_id');
        });

        DB::table('reviews')->update(['rating' => DB::raw('overall_rating')]);

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('overall_rating');
            $table->index('rating');
        });
    }
};
