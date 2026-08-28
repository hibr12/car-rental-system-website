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

        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_status_check');
        DB::statement("
            ALTER TABLE bookings
            ADD CONSTRAINT bookings_status_check
            CHECK (status IN (
                'pending',
                'confirmed',
                'active',
                'completed',
                'cancelled',
                'rejected',
                'pending_payment',
                'payment_verified',
                'pending_branch_approval',
                'pending_admin_approval',
                'branch_review',
                'ready_for_pickup',
                'return_pending',
                'expired',
                'payment_required',
                'payment_processing'
            ))
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_status_check');
        DB::statement("
            ALTER TABLE bookings
            ADD CONSTRAINT bookings_status_check
            CHECK (status IN (
                'pending',
                'confirmed',
                'active',
                'completed',
                'cancelled',
                'rejected',
                'pending_payment',
                'payment_verified',
                'pending_branch_approval',
                'pending_admin_approval',
                'branch_review',
                'ready_for_pickup',
                'return_pending',
                'expired'
            ))
        ");
    }
};