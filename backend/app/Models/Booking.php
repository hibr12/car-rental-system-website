<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    // ── Booking / rental lifecycle ────────────────────────────────────
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAYMENT_REQUIRED = 'payment_required';
    public const STATUS_PAYMENT_PROCESSING = 'payment_processing';
    public const STATUS_PAYMENT_VERIFIED = 'payment_verified';
    public const STATUS_PENDING_BRANCH_APPROVAL = 'pending_branch_approval';
    public const STATUS_PENDING_ADMIN_APPROVAL = 'pending_admin_approval';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETURN_PENDING = 'return_pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    /** @deprecated Use STATUS_PENDING_PAYMENT — kept for legacy row reads */
    public const STATUS_PENDING = 'pending';
    /** @deprecated Use STATUS_PENDING_BRANCH_APPROVAL */
    public const STATUS_BRANCH_REVIEW = 'branch_review';

    public const PAYMENT_STATUS_NOT_REQUIRED = 'not_required';
    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_CASH_PENDING = 'cash_pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const APPROVAL_NOT_REQUIRED = 'not_required';

    public const DOC_UNVERIFIED = 'unverified';
    public const DOC_VERIFIED = 'verified';
    public const DOC_NOT_REQUIRED = 'not_required';

    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING,
        self::APPROVAL_APPROVED,
        self::APPROVAL_REJECTED,
        self::APPROVAL_NOT_REQUIRED,
    ];

    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAYMENT_REQUIRED,
        self::STATUS_PAYMENT_PROCESSING,
        self::STATUS_PAYMENT_VERIFIED,
        self::STATUS_PENDING_BRANCH_APPROVAL,
        self::STATUS_PENDING_ADMIN_APPROVAL,
        self::STATUS_CONFIRMED,
        self::STATUS_READY_FOR_PICKUP,
        self::STATUS_ACTIVE,
        self::STATUS_RETURN_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
        // legacy
        self::STATUS_PENDING,
        self::STATUS_BRANCH_REVIEW,
    ];

    /** Statuses that hold the vehicle against overlapping bookings */
    public const BLOCKING_STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAYMENT_REQUIRED,
        self::STATUS_PAYMENT_PROCESSING,
        self::STATUS_PAYMENT_VERIFIED,
        self::STATUS_PENDING_BRANCH_APPROVAL,
        self::STATUS_PENDING_ADMIN_APPROVAL,
        self::STATUS_CONFIRMED,
        self::STATUS_READY_FOR_PICKUP,
        self::STATUS_ACTIVE,
        self::STATUS_RETURN_PENDING,
        self::STATUS_PENDING,
        self::STATUS_BRANCH_REVIEW,
    ];

    /** Statuses from which a customer/staff may still cancel */
    public const CANCELLABLE_STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAYMENT_VERIFIED,
        self::STATUS_PENDING_BRANCH_APPROVAL,
        self::STATUS_PENDING_ADMIN_APPROVAL,
        self::STATUS_CONFIRMED,
        self::STATUS_READY_FOR_PICKUP,
        self::STATUS_PENDING,
        self::STATUS_BRANCH_REVIEW,
    ];

    /** Statuses eligible for online/cash payment initiation (after branch/admin approval) */
    public const PAYABLE_STATUSES = [
        self::STATUS_PAYMENT_REQUIRED,
        self::STATUS_PAYMENT_PROCESSING,
        // legacy payment-before-approval rows
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PENDING,
        self::STATUS_PAYMENT_VERIFIED,
    ];

    public const PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_NOT_REQUIRED,
        self::PAYMENT_STATUS_UNPAID,
        self::PAYMENT_STATUS_PENDING,
        self::PAYMENT_STATUS_CASH_PENDING,
        self::PAYMENT_STATUS_PAID,
        self::PAYMENT_STATUS_FAILED,
        self::PAYMENT_STATUS_REFUNDED,
    ];

    protected $fillable = [
        'booking_reference',
        'user_id',
        'vehicle_id',
        'branch_id',
        'pickup_location',
        'return_location',
        'pickup_date',
        'return_date',
        'number_of_days',
        'price_per_day',
        'subtotal',
        'additional_charges',
        'discount',
        'total_price',
        'status',
        'payment_status',
        'branch_approval_status',
        'admin_approval_status',
        'admin_approval_required',
        'branch_approved_at',
        'branch_approved_by',
        'admin_approved_at',
        'admin_approved_by',
        'rejected_by_role',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'cancellation_source',
        'identity_verification_status',
        'license_verification_status',
        'documents_verified_at',
        'documents_verified_by',
        'picked_up_by',
        'picked_up_at',
        'pickup_branch_id',
        'pickup_mileage',
        'pickup_fuel_level',
        'returned_by',
        'returned_at',
        'review_reminder_sent_at',
        'return_mileage',
        'return_fuel_level',
        'return_condition_notes',
        'requires_maintenance',
        'override_by',
        'override_at',
        'override_reason',
        'notes',
        'is_archived',
        'archived_at',
        'archived_by',
        'archive_reason',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'datetime',
            'return_date' => 'datetime',
            'number_of_days' => 'integer',
            'price_per_day' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'additional_charges' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'user_id' => 'integer',
            'vehicle_id' => 'integer',
            'branch_id' => 'integer',
            'admin_approval_required' => 'boolean',
            'branch_approved_at' => 'datetime',
            'admin_approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'documents_verified_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'returned_at' => 'datetime',
            'review_reminder_sent_at' => 'datetime',
            'override_at' => 'datetime',
            'requires_maintenance' => 'boolean',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'pickup_mileage' => 'integer',
            'return_mileage' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function branchApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_approved_by');
    }

    public function adminApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function normalizeStatus(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => self::STATUS_PENDING_PAYMENT,
            self::STATUS_BRANCH_REVIEW => self::STATUS_PENDING_BRANCH_APPROVAL,
            default => $this->status,
        };
    }

    public function isPaymentRequired(): bool
    {
        return in_array($this->normalizeStatus(), [
            self::STATUS_PAYMENT_REQUIRED,
            self::STATUS_PAYMENT_PROCESSING,
        ], true)
            || in_array($this->payment_status, [
                self::PAYMENT_STATUS_PENDING,
                self::PAYMENT_STATUS_UNPAID,
            ], true);
    }

    public function isPaymentSatisfied(): bool
    {
        if ($this->payment_status !== self::PAYMENT_STATUS_PAID) {
            return false;
        }

        $payment = $this->relationLoaded('payments')
            ? $this->payments->firstWhere('status', Payment::STATUS_PAID)
            : $this->payments()->where('status', Payment::STATUS_PAID)->latest()->first();

        if (!$payment) {
            return false;
        }

        return $payment->isVerified();
    }

    public function isBranchApproved(): bool
    {
        return $this->branch_approval_status === self::APPROVAL_APPROVED
            || $this->branch_approval_status === self::APPROVAL_NOT_REQUIRED;
    }

    public function isAdminApproved(): bool
    {
        return $this->admin_approval_status === self::APPROVAL_APPROVED
            || $this->admin_approval_status === self::APPROVAL_NOT_REQUIRED
            || !$this->admin_approval_required;
    }

    public function canBecomeConfirmed(): bool
    {
        return $this->isPaymentSatisfied()
            && $this->isBranchApproved()
            && $this->isAdminApproved()
            && !in_array($this->normalizeStatus(), [
                self::STATUS_CANCELLED,
                self::STATUS_REJECTED,
                self::STATUS_EXPIRED,
                self::STATUS_COMPLETED,
                self::STATUS_ACTIVE,
                self::STATUS_RETURN_PENDING,
            ], true);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PENDING_PAYMENT]);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeInBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActiveRecords($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeOverlapping($query, $vehicleId, $pickupDate, $returnDate)
    {
        return $query->where('vehicle_id', $vehicleId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where(function ($q) use ($pickupDate, $returnDate) {
                $q->whereBetween('pickup_date', [$pickupDate, $returnDate])
                  ->orWhereBetween('return_date', [$pickupDate, $returnDate])
                  ->orWhere(function ($q2) use ($pickupDate, $returnDate) {
                      $q2->where('pickup_date', '<=', $pickupDate)
                         ->where('return_date', '>=', $returnDate);
                  });
            });
    }
}
