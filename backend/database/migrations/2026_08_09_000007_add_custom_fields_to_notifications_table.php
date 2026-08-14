<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable()->after('type');
            $table->text('message')->nullable()->after('title');
            $table->string('related_type')->nullable()->after('message');
            $table->unsignedBigInteger('related_id')->nullable()->after('related_type');
            $table->index('user_id');
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['related_type', 'related_id']);
            $table->dropColumn(['user_id', 'title', 'message', 'related_type', 'related_id']);
        });
    }
};
