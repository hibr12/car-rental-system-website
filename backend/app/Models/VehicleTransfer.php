<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleTransfer extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_READY_FOR_RELEASE = 'ready_for_release';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_RECEIVED_PENDING_INSPECTION = 'received_pending_inspection';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING,
        'requested',
        self::STATUS_APPROVED,
        self::STATUS_READY_FOR_RELEASE,
        self::STATUS_IN_TRANSIT,
        self::STATUS_RECEIVED,
        self::STATUS_RECEIVED_PENDING_INSPECTION,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED,
    ];

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        'requested',
        self::STATUS_APPROVED,
        self::STATUS_READY_FOR_RELEASE,
        self::STATUS_IN_TRANSIT,
        self::STATUS_RECEIVED,
        self::STATUS_RECEIVED_PENDING_INSPECTION,
    ];

    protected $fillable = [
        'vehicle_id',
        'from_branch_id',
        'to_branch_id',
        'requested_by',
        'approved_by',
        'released_by',
        'received_by',
        'completed_by',
        'rejected_by',
        'cancelled_by',
        'started_by',
        'failed_by',
        'transfer_date',
        'reason',
        'status',
        'notes',
        'request_notes',
        'approval_notes',
        'release_notes',
        'receiving_notes',
        'damage_report',
        'failure_reason',
        'rejection_reason',
        'cancellation_reason',
        'source_odometer',
        'destination_odometer',
        'source_fuel_level',
        'destination_fuel_level',
        'source_condition',
        'destination_condition',
        'requested_at',
        'approved_at',
        'released_at',
        'in_transit_at',
        'received_at',
        'completed_at',
        'rejected_at',
        'cancelled_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
            'in_transit_at' => 'datetime',
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function releasedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeRequested($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, 'requested']);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_READY_FOR_RELEASE]);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }
}
