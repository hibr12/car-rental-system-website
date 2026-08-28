<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check');
        DB::statement("
            ALTER TABLE payments
            ADD CONSTRAINT payments_status_check
            CHECK (status IN (
                'pending',
                'processing',
                'cash_pending',
                'paid',
                'failed',
                'cancelled',
                'expired',
                'invalid',
                'refund_pending',
                'partially_refunded',
                'refunded',
                'disputed',
                'unpaid'
            ))
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check');
        DB::statement("
            ALTER TABLE payments
            ADD CONSTRAINT payments_status_check
            CHECK (status IN (
                'pending',
                'paid',
                'failed',
                'refunded'
            ))
        ");
    }
};