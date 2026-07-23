<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('brand');
            $table->string('model');
            $table->year('year');
            $table->string('registration_number')->unique();
            $table->string('vin_number')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('fuel_type');
            $table->string('transmission');
            $table->unsignedSmallInteger('seats');
            $table->string('color')->nullable();
            $table->unsignedInteger('mileage')->default(0);
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('rental_price_per_day', 10, 2);
            $table->string('status')->default('available');
            $table->boolean('featured')->default(false);
            $table->string('location')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index('brand');
            $table->index('featured');
            $table->index('rental_price_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
