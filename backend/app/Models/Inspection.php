<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'vehicle_id',
        'inspected_by',
        'inspection_type',
        'mileage_at_inspection',
        'fuel_level_full',
        'has_damage',
        'damage_description',
        'notes',
        'condition_rating',
        'inspected_at',
    ];

    protected function casts(): array
    {
        return [
            'mileage_at_inspection' => 'decimal:2',
            'fuel_level_full' => 'boolean',
            'has_damage' => 'boolean',
            'inspected_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
