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

    public const STATUS_PENDING = 'pending';
    public const STATUS_BRANCH_REVIEW = 'branch_review';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_CASH_PENDING = 'cash_pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    public const APPROVAL_PENDING  = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING,
        self::APPROVAL_APPROVED,
        self::APPROVAL_REJECTED,
    ];

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_BRANCH_REVIEW,
        self::STATUS_CONFIRMED,
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
    ];

    public const PAYMENT_STATUSES = [
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
        'branch_approved_at',
        'branch_approved_by',
        'admin_approved_at',
        'admin_approved_by',
        'rejected_by_role',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
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
            'branch_approved_at' => 'datetime',
            'admin_approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
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

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
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
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_ACTIVE])
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
