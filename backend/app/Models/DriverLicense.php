<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverLicense extends Model
{
    use HasFactory, SoftDeletes;

    // ── Status constants ──────────────────────────────────────────────────────
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_VERIFIED       = 'verified';
    public const STATUS_REJECTED       = 'rejected';
    public const STATUS_EXPIRED        = 'expired';
    public const STATUS_REPLACED       = 'replaced';

    public const STATUSES = [
        self::STATUS_PENDING_REVIEW,
        self::STATUS_VERIFIED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
        self::STATUS_REPLACED,
    ];

    /** Statuses that are "active" (the customer's current license attempt). */
    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING_REVIEW,
        self::STATUS_VERIFIED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
    ];

    // ── License categories ────────────────────────────────────────────────────
    public const CATEGORY_AUTOMOBILE   = 'automobile';   // Standard car (Class B)
    public const CATEGORY_MOTORCYCLE   = 'motorcycle';   // Motorcycle (Class A)
    public const CATEGORY_COMMERCIAL   = 'commercial';   // Trucks / commercial (Class C+)
    public const CATEGORY_MINIBUS      = 'minibus';      // Minibus / small bus
    public const CATEGORY_HEAVY        = 'heavy';        // Heavy vehicles

    public const CATEGORIES = [
        self::CATEGORY_AUTOMOBILE,
        self::CATEGORY_MOTORCYCLE,
        self::CATEGORY_COMMERCIAL,
        self::CATEGORY_MINIBUS,
        self::CATEGORY_HEAVY,
    ];

    /** Categories that can drive an 'automobile' vehicle (higher licences include lower). */
    public const AUTOMOBILE_ELIGIBLE_CATEGORIES = [
        self::CATEGORY_AUTOMOBILE,
        self::CATEGORY_COMMERCIAL,
        self::CATEGORY_MINIBUS,
        self::CATEGORY_HEAVY,
    ];

    protected $fillable = [
        'user_id',
        'license_number',
        'full_name',
        'date_of_birth',
        'license_category',
        'issue_date',
        'expiry_date',
        'issuing_authority',
        'issuing_country',
        'front_document_path',
        'back_document_path',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'submitted_at',
        'replaced_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'issue_date'    => 'date',
        'expiry_date'   => 'date',
        'verified_at'   => 'datetime',
        'submitted_at'  => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function replacedByLicense(): BelongsTo
    {
        return $this->belongsTo(DriverLicense::class, 'replaced_by');
    }

    // ── Status helpers ────────────────────────────────────────────────────────

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expiry_date !== null && $this->expiry_date->isPast());
    }

    public function isReplaced(): bool
    {
        return $this->status === self::STATUS_REPLACED;
    }

    /**
     * Effective status — if the stored status is VERIFIED but expiry_date is past,
     * treat it as EXPIRED at runtime without requiring a batch job to update every row.
     */
    public function effectiveStatus(): string
    {
        if (
            in_array($this->status, [self::STATUS_VERIFIED, self::STATUS_PENDING_REVIEW], true)
            && $this->expiry_date !== null
            && $this->expiry_date->isPast()
        ) {
            return self::STATUS_EXPIRED;
        }

        return $this->status;
    }

    /**
     * Whether this license satisfies booking eligibility for a given vehicle category.
     *
     * @param string|null $requiredCategory Vehicle's required_license_category (null = any/none)
     */
    public function satisfiesEligibility(?string $requiredCategory = null): bool
    {
        if ($this->effectiveStatus() !== self::STATUS_VERIFIED) {
            return false;
        }

        if ($requiredCategory === null) {
            return true;
        }

        return $this->categoryCovers($requiredCategory);
    }

    /**
     * Whether this license category covers the required vehicle category.
     * Higher categories (commercial, heavy) also satisfy lower requirements (automobile).
     */
    public function categoryCovers(string $requiredCategory): bool
    {
        if ($this->license_category === $requiredCategory) {
            return true;
        }

        if ($requiredCategory === self::CATEGORY_AUTOMOBILE) {
            return in_array($this->license_category, self::AUTOMOBILE_ELIGIBLE_CATEGORIES, true);
        }

        return false;
    }

    /**
     * Days remaining before expiry. Negative means already expired.
     */
    public function daysUntilExpiry(): int
    {
        if ($this->expiry_date === null) {
            return PHP_INT_MAX;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    /**
     * Masked license number for display in lists/logs.
     * Example: "••••••••4821"
     */
    public function maskedLicenseNumber(): string
    {
        $number = $this->license_number ?? '';
        $len = mb_strlen($number);
        if ($len <= 4) {
            return str_repeat('•', $len);
        }

        return str_repeat('•', $len - 4) . mb_substr($number, -4);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    public function scopeForCustomer($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
