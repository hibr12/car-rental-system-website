<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'expected_amount')) {
                $table->decimal('expected_amount', 12, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->nullable()->after('expected_amount');
            }
            if (!Schema::hasColumn('payments', 'mismatch_reason')) {
                $table->string('mismatch_reason')->nullable()->after('failure_reason');
            }
            if (!Schema::hasColumn('payments', 'attempt_number')) {
                $table->unsignedInteger('attempt_number')->default(1)->after('booking_id');
            }
            if (!Schema::hasColumn('payments', 'refund_amount')) {
                $table->decimal('refund_amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable();
            }
            if (!Schema::hasColumn('payments', 'amount_received')) {
                $table->decimal('amount_received', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('payments', 'idempotency_key')) {
                $table->string('idempotency_key')->nullable()->unique();
            }
        });

        // Backfill expected_amount from amount; attempt numbers per booking
        DB::table('payments')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $updates = [];
                if ($row->expected_amount === null) {
                    $updates['expected_amount'] = $row->amount;
                }
                if (in_array($row->status, ['paid', 'refunded'], true) && $row->paid_amount === null) {
                    $updates['paid_amount'] = $row->amount;
                }
                if (!empty($updates)) {
                    DB::table('payments')->where('id', $row->id)->update($updates);
                }
            }
        });

        // Assign attempt_number by booking chronological order
        $bookingIds = DB::table('payments')->distinct()->pluck('booking_id');
        foreach ($bookingIds as $bookingId) {
            $payments = DB::table('payments')
                ->where('booking_id', $bookingId)
                ->orderBy('id')
                ->get(['id']);
            $n = 1;
            foreach ($payments as $p) {
                DB::table('payments')->where('id', $p->id)->update(['attempt_number' => $n++]);
            }
        }

        // Flag paid-but-unverified gateway payments for reconciliation (do not invent verification)
        DB::table('payments')
            ->where('status', 'paid')
            ->where('payment_method', 'online_payment')
            ->where(function ($q) {
                $q->whereNull('verification_status')
                    ->orWhere('verification_status', 'unverified');
            })
            ->update([
                'verification_status' => 'verification_error',
                'failure_reason' => 'Legacy paid record without gateway verification evidence — requires reconciliation',
            ]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach ([
                'expected_amount', 'paid_amount', 'mismatch_reason', 'attempt_number',
                'refund_amount', 'refunded_at', 'amount_received', 'idempotency_key',
            ] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
