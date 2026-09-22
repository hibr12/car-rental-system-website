<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_payment_status_check');
        DB::statement("
            ALTER TABLE bookings 
            ADD CONSTRAINT bookings_payment_status_check 
            CHECK (payment_status IN (
                'unpaid',
                'pending',
                'cash_pending',
                'not_required',
                'paid',
                'failed',
                'refunded'
            ))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_payment_status_check');
        DB::statement("
            ALTER TABLE bookings 
            ADD CONSTRAINT bookings_payment_status_check 
            CHECK (payment_status IN (
                'unpaid',
                'pending',
                'paid',
                'failed',
                'refunded'
            ))
        ");
    }
};