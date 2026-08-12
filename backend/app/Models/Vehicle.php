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
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_RENTED = 'rented';
    public const STATUS_RETURN_PENDING_INSPECTION = 'return_pending_inspection';
    public const STATUS_INSPECTION_REQUIRED = 'inspection_required';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_UNAVAILABLE = 'unavailable';
    public const STATUS_TRANSFER_PENDING = 'transfer_pending';
    public const STATUS_TRANSFER_IN_TRANSIT = 'transfer_in_transit';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_RETIRED = 'retired';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_RESERVED,
        self::STATUS_READY_FOR_PICKUP,
        self::STATUS_RENTED,
        self::STATUS_RETURN_PENDING_INSPECTION,
        self::STATUS_INSPECTION_REQUIRED,
        self::STATUS_MAINTENANCE,
        self::STATUS_UNAVAILABLE,
        self::STATUS_TRANSFER_PENDING,
        self::STATUS_TRANSFER_IN_TRANSIT,
        self::STATUS_TRANSFERRED,
        self::STATUS_RETIRED,
    ];

    /** Statuses that block customer booking */
    public const NON_RENTABLE_STATUSES = [
        self::STATUS_MAINTENANCE,
        self::STATUS_UNAVAILABLE,
        self::STATUS_RETIRED,
        self::STATUS_TRANSFER_PENDING,
        self::STATUS_TRANSFER_IN_TRANSIT,
        self::STATUS_RETURN_PENDING_INSPECTION,
        self::STATUS_INSPECTION_REQUIRED,
        self::STATUS_RENTED,
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
        'condition',
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

    public function inspections(): HasMany
    {
        return $this->hasMany(VehicleInspection::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(VehicleDamage::class);
    }

    public function activeTransfer()
    {
        return $this->hasOne(VehicleTransfer::class)
            ->whereIn('status', VehicleTransfer::ACTIVE_STATUSES)
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
        return $this->isRentable();
    }

    public function isRentable(): bool
    {
        if (!$this->branch_id) {
            return false;
        }

        if (in_array($this->status, self::NON_RENTABLE_STATUSES, true)) {
            return false;
        }

        if ($this->hasExpiredRequiredDocuments()) {
            return false;
        }

        return in_array($this->status, [self::STATUS_AVAILABLE, self::STATUS_RESERVED], true);
    }

    public function hasExpiredRequiredDocuments(): bool
    {
        return $this->documents()
            ->where('is_required', true)
            ->where(function ($q) {
                $q->where('status', VehicleDocument::STATUS_EXPIRED)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('expiry_date')
                            ->whereDate('expiry_date', '<', now()->toDateString());
                    });
            })
            ->exists();
    }

    public function hasBlockingActiveBooking(): bool
    {
        return $this->bookings()
            ->whereIn('status', [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_READY_FOR_PICKUP,
                Booking::STATUS_ACTIVE,
                Booking::STATUS_RETURN_PENDING,
            ])
            ->exists();
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
