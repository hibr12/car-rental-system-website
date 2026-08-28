<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDamage extends Model
{
    use HasFactory;

    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    public const REPAIR_PENDING = 'pending';
    public const REPAIR_IN_PROGRESS = 'in_progress';
    public const REPAIR_COMPLETED = 'completed';
    public const REPAIR_WAIVED = 'waived';

    protected $fillable = [
        'vehicle_id',
        'booking_id',
        'inspection_id',
        'reported_by',
        'damage_type',
        'description',
        'severity',
        'location',
        'photos',
        'estimated_repair_cost',
        'repair_status',
        'reported_at',
        'repaired_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'estimated_repair_cost' => 'decimal:2',
            'reported_at' => 'datetime',
            'repaired_at' => 'datetime',
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

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(VehicleInspection::class, 'inspection_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
