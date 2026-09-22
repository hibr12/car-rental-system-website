<?php

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing check constraint first
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_status_check');
        
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'admin_approval_required')) {
                $table->boolean('admin_approval_required')->default(false)->after('admin_approval_status');
            }
            if (!Schema::hasColumn('bookings', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'cancellation_source')) {
                $table->string('cancellation_source')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'identity_verification_status')) {
                $table->string('identity_verification_status')->default('unverified');
            }
            if (!Schema::hasColumn('bookings', 'license_verification_status')) {
                $table->string('license_verification_status')->default('unverified');
            }
            if (!Schema::hasColumn('bookings', 'documents_verified_at')) {
                $table->timestamp('documents_verified_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'documents_verified_by')) {
                $table->foreignId('documents_verified_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'picked_up_by')) {
                $table->foreignId('picked_up_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'picked_up_at')) {
                $table->timestamp('picked_up_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'pickup_branch_id')) {
                $table->foreignId('pickup_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'pickup_mileage')) {
                $table->unsignedInteger('pickup_mileage')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'pickup_fuel_level')) {
                $table->string('pickup_fuel_level')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'returned_by')) {
                $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'returned_at')) {
                $table->timestamp('returned_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'return_mileage')) {
                $table->unsignedInteger('return_mileage')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'return_fuel_level')) {
                $table->string('return_fuel_level')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'return_condition_notes')) {
                $table->text('return_condition_notes')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'requires_maintenance')) {
                $table->boolean('requires_maintenance')->default(false);
            }
            if (!Schema::hasColumn('bookings', 'override_by')) {
                $table->foreignId('override_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'override_at')) {
                $table->timestamp('override_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'override_reason')) {
                $table->text('override_reason')->nullable();
            }
        });

        $this->normalizeLegacyBookings();
        
        // Add new check constraint with updated allowed values
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_status_check CHECK (status IN ('pending', 'confirmed', 'active', 'completed', 'cancelled', 'rejected', 'pending_payment', 'payment_verified', 'pending_branch_approval', 'pending_admin_approval', 'branch_review', 'ready_for_pickup', 'return_pending', 'expired'))");
    }

    private function normalizeLegacyBookings(): void
    {
        $bookings = DB::table('bookings')->orderBy('id')->get();

        foreach ($bookings as $row) {
            $updates = [];
            $status = $row->status;
            $paymentStatus = $row->payment_status;
            $branchApproval = $row->branch_approval_status ?? 'pending';
            $adminApproval = $row->admin_approval_status ?? 'pending';

            $paidPayment = DB::table('payments')
                ->where('booking_id', $row->id)
                ->where('status', 'paid')
                ->orderByDesc('id')
                ->first();

            $paymentVerified = $paidPayment && in_array($paidPayment->verification_status ?? null, [
                'verified',
                'manually_confirmed',
            ], true);

            // Preserve terminal / in-progress operational states
            if (in_array($status, ['active', 'completed', 'cancelled', 'rejected', 'expired', 'return_pending', 'ready_for_pickup'], true)) {
                if ($adminApproval === 'pending' && $branchApproval === 'approved') {
                    // Already past confirmation — mark admin not required if never decided
                    $updates['admin_approval_status'] = 'not_required';
                    $updates['admin_approval_required'] = false;
                } elseif ($adminApproval === 'pending' && $branchApproval === 'pending' && in_array($status, ['active', 'completed', 'ready_for_pickup'], true)) {
                    // Operational without recorded approvals — treat as historically approved
                    $updates['branch_approval_status'] = 'approved';
                    $updates['admin_approval_status'] = 'not_required';
                    $updates['admin_approval_required'] = false;
                }

                if (!empty($updates)) {
                    DB::table('bookings')->where('id', $row->id)->update($updates);
                }
                continue;
            }

            // Map legacy pending / confirmed contradictions
            if (in_array($status, ['pending', 'branch_review', 'pending_payment', 'payment_verified', 'pending_branch_approval', 'pending_admin_approval', 'confirmed'], true)) {
                if (!$paymentVerified && $paymentStatus !== 'paid') {
                    $updates['status'] = 'pending_payment';
                    $updates['branch_approval_status'] = $branchApproval === 'approved' ? 'approved' : 'pending';
                    // Normal bookings: admin not required until special rules apply later
                    if ($adminApproval === 'pending') {
                        $updates['admin_approval_status'] = 'not_required';
                        $updates['admin_approval_required'] = false;
                    }
                } elseif ($paymentVerified || $paymentStatus === 'paid') {
                    $updates['payment_status'] = 'paid';

                    if ($branchApproval === 'rejected' || $adminApproval === 'rejected') {
                        $updates['status'] = 'rejected';
                    } elseif ($branchApproval !== 'approved') {
                        $updates['status'] = 'pending_branch_approval';
                        $updates['branch_approval_status'] = 'pending';
                        if ($adminApproval === 'pending') {
                            $updates['admin_approval_status'] = 'not_required';
                            $updates['admin_approval_required'] = false;
                        }
                    } elseif ($adminApproval === 'pending' && (bool) ($row->admin_approval_required ?? false)) {
                        $updates['status'] = 'pending_admin_approval';
                    } elseif ($adminApproval === 'pending') {
                        // Branch approved, admin was pending but not required by business rules
                        $updates['admin_approval_status'] = 'not_required';
                        $updates['admin_approval_required'] = false;
                        $updates['status'] = 'confirmed';
                    } elseif (in_array($adminApproval, ['approved', 'not_required'], true)) {
                        $updates['status'] = 'confirmed';
                    }
                } elseif ($paymentStatus === 'cash_pending' || $paymentStatus === 'pending') {
                    $updates['status'] = 'pending_payment';
                    if ($adminApproval === 'pending') {
                        $updates['admin_approval_status'] = 'not_required';
                        $updates['admin_approval_required'] = false;
                    }
                }

                // Fix invalid: confirmed while approvals pending
                if (($status === 'confirmed' || ($updates['status'] ?? null) === 'confirmed')
                    && (($updates['branch_approval_status'] ?? $branchApproval) === 'pending'
                        || (($updates['admin_approval_status'] ?? $adminApproval) === 'pending'
                            && ($updates['admin_approval_required'] ?? $row->admin_approval_required ?? false)))) {
                    if (($updates['branch_approval_status'] ?? $branchApproval) === 'pending') {
                        if ($paymentVerified || $paymentStatus === 'paid') {
                            $updates['status'] = 'pending_branch_approval';
                        } else {
                            $updates['status'] = 'pending_payment';
                        }
                    } elseif (($updates['admin_approval_status'] ?? $adminApproval) === 'pending') {
                        $updates['status'] = 'pending_admin_approval';
                        $updates['admin_approval_required'] = true;
                    }
                }
            }

            // Legacy status aliases
            if (($updates['status'] ?? $status) === 'pending') {
                $updates['status'] = 'pending_payment';
            }
            if (($updates['status'] ?? $status) === 'branch_review') {
                $updates['status'] = 'pending_branch_approval';
            }

            if (!empty($updates)) {
                DB::table('bookings')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'admin_approval_required',
                'cancelled_by', 'cancelled_at', 'cancellation_reason', 'cancellation_source',
                'identity_verification_status', 'license_verification_status',
                'documents_verified_at', 'documents_verified_by',
                'picked_up_by', 'picked_up_at', 'pickup_branch_id', 'pickup_mileage', 'pickup_fuel_level',
                'returned_by', 'returned_at', 'return_mileage', 'return_fuel_level', 'return_condition_notes',
                'requires_maintenance',
                'override_by', 'override_at', 'override_reason',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    try {
                        if (in_array($col, [
                            'cancelled_by', 'documents_verified_by', 'picked_up_by',
                            'pickup_branch_id', 'returned_by', 'override_by',
                        ], true)) {
                            $table->dropForeign([$col]);
                        }
                    } catch (\Throwable) {
                        // ignore missing FK names on sqlite
                    }
                    $table->dropColumn($col);
                }
            }
        });
        
        // Restore original check constraint
        DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS bookings_status_check');
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_status_check CHECK (status IN ('pending', 'confirmed', 'active', 'completed', 'cancelled', 'rejected'))");
    }
};