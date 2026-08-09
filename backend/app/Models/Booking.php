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
        'notes',
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

    public function pickupInspection(): HasOne
    {
        return $this->hasOne(Inspection::class)->where('inspection_type', 'pickup');
    }

    public function returnInspection(): HasOne
    {
        return $this->hasOne(Inspection::class)->where('inspection_type', 'return');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOverlapping($query, $vehicleId, $pickupDate, $returnDate)
    {
        return $query->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['pending', 'confirmed', 'active'])
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