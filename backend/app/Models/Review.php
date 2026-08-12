<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_FLAGGED = 'flagged';
    public const STATUS_ARCHIVED = 'archived';

    /** @deprecated Use STATUS_PUBLISHED */
    public const STATUS_PENDING = 'pending';
    /** @deprecated Use STATUS_PUBLISHED */
    public const STATUS_APPROVED = 'approved';
    /** @deprecated Use STATUS_HIDDEN */
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PUBLISHED,
        self::STATUS_HIDDEN,
        self::STATUS_FLAGGED,
        self::STATUS_ARCHIVED,
    ];

    public const PUBLIC_STATUSES = [
        self::STATUS_PUBLISHED,
    ];

    public const MIN_RATING = 1;
    public const MAX_RATING = 5;
    public const EDIT_WINDOW_HOURS = 48;
    public const MAX_COMMENT_LENGTH = 1000;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'booking_id',
        'branch_id',
        'overall_rating',
        'vehicle_rating',
        'cleanliness_rating',
        'staff_rating',
        'value_rating',
        'comment',
        'status',
        'admin_response',
        'admin_response_at',
        'admin_response_by',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating' => 'integer',
            'vehicle_rating' => 'integer',
            'cleanliness_rating' => 'integer',
            'staff_rating' => 'integer',
            'value_rating' => 'integer',
            'admin_response_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->user();
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

    public function adminResponder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_response_by');
    }

    public function scopePublished($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PUBLISHED,
            self::STATUS_APPROVED,
            self::STATUS_PENDING,
        ]);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /** @deprecated Use scopePublished */
    public function scopeApproved($query)
    {
        return $this->scopePublished($query);
    }

    public function isVerifiedRental(): bool
    {
        if (!$this->booking_id) {
            return false;
        }

        $this->loadMissing('booking');

        return $this->booking
            && $this->booking->status === Booking::STATUS_COMPLETED
            && $this->booking->picked_up_at !== null
            && $this->booking->returned_at !== null;
    }

    public function isEditableByCustomer(): bool
    {
        return $this->created_at !== null
            && $this->created_at->gt(now()->subHours(self::EDIT_WINDOW_HOURS));
    }

    public function getRatingAttribute(): ?int
    {
        return $this->overall_rating;
    }
}
