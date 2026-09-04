<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_licenses', function (Blueprint $table) {
            $table->id();

            // Customer who owns this license.
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // License details provided by the customer.
            $table->string('license_number', 100);
            $table->string('full_name', 200);
            $table->date('date_of_birth')->nullable();
            $table->string('license_category', 50)->default('automobile');
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('issuing_authority', 200)->nullable();
            $table->string('issuing_country', 100)->nullable();

            // Secure document paths (private storage — never public URLs).
            $table->string('front_document_path')->nullable();
            $table->string('back_document_path')->nullable();

            // Verification lifecycle.
            // Values: not_submitted, pending_review, verified, rejected, expired, replaced
            $table->string('status', 30)->default('pending_review');

            $table->text('rejection_reason')->nullable();

            // Reviewer audit.
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('submitted_at')->useCurrent();

            // When a customer uploads a replacement, the old record gets status=replaced
            // and this field points to the new active record.
            $table->foreignId('replaced_by')
                ->nullable()
                ->constrained('driver_licenses')
                ->nullOnDelete();

            // Soft timestamps — never hard-delete license records (audit history).
            $table->timestamps();
            $table->softDeletes();

            // Indexes.
            $table->index('user_id');
            $table->index('status');
            $table->index('expiry_date');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_licenses');
    }
};
