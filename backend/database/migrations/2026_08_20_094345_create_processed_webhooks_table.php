<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('processed_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('source')->index(); // e.g., 'chapa'
            $table->string('event_id')->unique(); // Chapa's event ID or tx_ref + event type
            $table->string('event_type')->nullable(); // e.g., 'transaction.success'
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->useCurrent();
            
            $table->index(['source', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_webhooks');
    }
};
