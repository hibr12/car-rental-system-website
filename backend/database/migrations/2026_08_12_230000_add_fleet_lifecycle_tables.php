<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'condition')) {
                $table->string('condition')->default('good')->after('status');
            }
        });

        Schema::create('vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspection_type');
            $table->dateTime('inspected_at')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->unsignedTinyInteger('fuel_level')->nullable();
            $table->string('exterior_condition')->nullable();
            $table->string('interior_condition')->nullable();
            $table->string('tires_condition')->nullable();
            $table->string('lights_condition')->nullable();
            $table->string('brakes_condition')->nullable();
            $table->string('engine_indicators')->nullable();
            $table->boolean('has_damage')->default(false);
            $table->text('damage_notes')->nullable();
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();
            $table->string('result')->default('pending');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['vehicle_id', 'inspection_type']);
            $table->index(['vehicle_id', 'status']);
            $table->index('result');
        });

        Schema::create('vehicle_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('valid');
            $table->string('attachment_url')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_required')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'document_type']);
            $table->index(['expiry_date', 'status']);
        });

        Schema::create('vehicle_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained('vehicle_inspections')->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('damage_type');
            $table->text('description');
            $table->string('severity')->default('medium');
            $table->string('location')->nullable();
            $table->json('photos')->nullable();
            $table->decimal('estimated_repair_cost', 10, 2)->nullable();
            $table->string('repair_status')->default('pending');
            $table->dateTime('reported_at');
            $table->dateTime('repaired_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'repair_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_damages');
        Schema::dropIfExists('vehicle_documents');
        Schema::dropIfExists('vehicle_inspections');

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'condition')) {
                $table->dropColumn('condition');
            }
        });
    }
};
