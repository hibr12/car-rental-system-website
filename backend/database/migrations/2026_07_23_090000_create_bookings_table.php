<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('pickup_location');
            $table->string('return_location');
            $table->dateTime('pickup_date');
            $table->dateTime('return_date');
            $table->integer('number_of_days');
            $table->decimal('price_per_day', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('additional_charges', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'rejected'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'failed', 'refunded'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('booking_reference');
            $table->index(['user_id', 'status']);
            $table->index(['vehicle_id', 'status']);
            $table->index('pickup_date');
            $table->index('return_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};