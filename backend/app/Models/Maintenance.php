<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;

    public const TYPE_OIL_CHANGE = 'oil_change';
    public const TYPE_TIRE_SERVICE = 'tire_service';
    public const TYPE_BRAKE_SERVICE = 'brake_service';
    public const TYPE_ENGINE_REPAIR = 'engine_repair';
    public const TYPE_ELECTRICAL = 'electrical';
    public const TYPE_INSPECTION = 'inspection';
    public const TYPE_CLEANING = 'cleaning';
    public const TYPE_GENERAL_SERVICE = 'general_service';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_OIL_CHANGE,
        self::TYPE_TIRE_SERVICE,
        self::TYPE_BRAKE_SERVICE,
        self::TYPE_ENGINE_REPAIR,
        self::TYPE_ELECTRICAL,
        self::TYPE_INSPECTION,
        self::TYPE_CLEANING,
        self::TYPE_GENERAL_SERVICE,
        self::TYPE_OTHER,
    ];

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'vehicle_id',
        'branch_id',
        'title',
        'description',
        'maintenance_type',
        'cost',
        // Table columns (migration) use start_date/end_date, so they must be mass-assignable.
        'start_date',
        'end_date',
        'mileage',
        'service_date',
        'next_service_date',
        'performed_by',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'service_date' => 'datetime',
            'next_service_date' => 'datetime',
            'mileage' => 'integer',
            'created_by' => 'integer',
            'vehicle_id' => 'integer',
            'branch_id' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeInBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS]);
    }
}
