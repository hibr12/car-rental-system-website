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
        Schema::table('driver_licenses', function (Blueprint $table) {
            // Add document_type column with default 'driver_license' for backward compatibility
            $table->string('document_type', 30)->default('driver_license')->after('user_id');
            
            // Rename license_number to document_number (keep old column for backward compatibility)
            $table->renameColumn('license_number', 'document_number');
            
            // Add new fields for different document types
            $table->string('university_name', 200)->nullable()->after('issuing_country');
            $table->string('department', 200)->nullable()->after('university_name');
            
            // Make driver_license specific fields nullable for other document types
            $table->string('license_category', 50)->nullable()->change();
            $table->date('issue_date')->nullable()->change();
            $table->date('expiry_date')->nullable()->change();
            
            // Add document_type index
            $table->index('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_licenses', function (Blueprint $table) {
            // Drop the new columns
            $table->dropColumn('document_type');
            $table->dropColumn('university_name');
            $table->dropColumn('department');
            
            // Revert column changes
            $table->string('license_category', 50)->default('automobile')->change();
            $table->date('issue_date')->nullable(false)->change();
            $table->date('expiry_date')->nullable(false)->change();
            
            // Rename back
            $table->renameColumn('document_number', 'license_number');
            
            // Drop index
            $table->dropIndex(['document_type']);
        });
    }
};