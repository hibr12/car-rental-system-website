<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleInspection extends Model
{
    use HasFactory;

    public const TYPE_PRE_RENTAL = 'pre_rental';
    public const TYPE_POST_RETURN = 'post_return';
    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_PERIODIC = 'periodic';
    public const TYPE_TRANSFER = 'transfer';

    public const TYPES = [
        self::TYPE_PRE_RENTAL,
        self::TYPE_POST_RETURN,
        self::TYPE_MAINTENANCE,
        self::TYPE_PERIODIC,
        self::TYPE_TRANSFER,
    ];

    public const RESULT_PENDING = 'pending';
    public const RESULT_PASSED = 'passed';
    public const RESULT_FAILED = 'failed';
    public const RESULT_REQUIRES_MAINTENANCE = 'requires_maintenance';

    public const RESULTS = [
        self::RESULT_PENDING,
        self::RESULT_PASSED,
        self::RESULT_FAILED,
        self::RESULT_REQUIRES_MAINTENANCE,
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'vehicle_id',
        'booking_id',
        'branch_id',
        'inspector_id',
        'inspection_type',
        'inspected_at',
        'mileage',
        'fuel_level',
        'exterior_condition',
        'interior_condition',
        'tires_condition',
        'lights_condition',
        'brakes_condition',
        'engine_indicators',
        'has_damage',
        'damage_notes',
        'photos',
        'notes',
        'result',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'inspected_at' => 'datetime',
            'mileage' => 'integer',
            'fuel_level' => 'integer',
            'has_damage' => 'boolean',
            'photos' => 'array',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
