<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_RENTED = 'rented';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_UNAVAILABLE = 'unavailable';
    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_RESERVED,
        self::STATUS_RENTED,
        self::STATUS_MAINTENANCE,
        self::STATUS_UNAVAILABLE,
        self::STATUS_TRANSFERRED,
    ];

    protected $fillable = [
        'category_id',
        'branch_id',
        'brand',
        'model',
        'year',
        'registration_number',
        'vin_number',
        'description',
        'fuel_type',
        'transmission',
        'seats',
        'color',
        'mileage',
        'purchase_price',
        'rental_price_per_day',
        'status',
        'featured',
        'location',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'seats' => 'integer',
            'mileage' => 'integer',
            'purchase_price' => 'decimal:2',
            'rental_price_per_day' => 'decimal:2',
            'featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function primaryImage()
    {
        return $this->hasOne(VehicleImage::class)->where('is_primary', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeInBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(VehicleTransfer::class);
    }

    public function activeTransfer()
    {
        return $this->hasOne(VehicleTransfer::class)
            ->whereIn('status', [
                VehicleTransfer::STATUS_PENDING,
                VehicleTransfer::STATUS_APPROVED,
                VehicleTransfer::STATUS_IN_TRANSIT,
            ])
            ->latest();
    }

    public function completedTransfers(): HasMany
    {
        return $this->hasMany(VehicleTransfer::class)
            ->where('status', VehicleTransfer::STATUS_COMPLETED)
            ->orderByDesc('completed_at');
    }

    public function averageRating(): float
    {
        return (float) ($this->reviews()->publiclyVisible()->avg('overall_rating') ?? 0);
    }

    public function publishedReviewCount(): int
    {
        return $this->reviews()->publiclyVisible()->count();
    }

    public function canBeBooked(): bool
    {
        return in_array($this->status, [self::STATUS_AVAILABLE, self::STATUS_RESERVED]);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isRented(): bool
    {
        return $this->status === self::STATUS_RENTED;
    }

    public function isUnderMaintenance(): bool
    {
        return $this->status === self::STATUS_MAINTENANCE;
    }
}
