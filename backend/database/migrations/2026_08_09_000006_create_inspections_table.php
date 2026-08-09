<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspected_by')->constrained('users')->cascadeOnDelete();
            $table->string('inspection_type');
            $table->decimal('mileage_at_inspection', 10, 2)->nullable();
            $table->boolean('fuel_level_full')->default(true);
            $table->boolean('has_damage')->default(false);
            $table->text('damage_description')->nullable();
            $table->text('notes')->nullable();
            $table->string('condition_rating')->nullable();
            $table->timestamp('inspected_at');
            $table->timestamps();

            $table->index(['booking_id', 'inspection_type']);
            $table->index(['vehicle_id', 'inspection_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
