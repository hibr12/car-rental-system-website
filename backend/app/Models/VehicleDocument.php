<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDocument extends Model
{
    use HasFactory;

    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_INSURANCE = 'insurance';
    public const TYPE_INSPECTION_CERT = 'inspection_certificate';
    public const TYPE_ROADWORTHINESS = 'roadworthiness';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_REGISTRATION,
        self::TYPE_INSURANCE,
        self::TYPE_INSPECTION_CERT,
        self::TYPE_ROADWORTHINESS,
        self::TYPE_OTHER,
    ];

    public const STATUS_VALID = 'valid';
    public const STATUS_EXPIRING_SOON = 'expiring_soon';
    public const STATUS_EXPIRED = 'expired';

    public const EXPIRING_SOON_DAYS = 30;

    protected $fillable = [
        'vehicle_id',
        'document_type',
        'document_number',
        'issue_date',
        'expiry_date',
        'status',
        'attachment_url',
        'notes',
        'is_required',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'is_required' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function refreshStatus(): void
    {
        if (!$this->expiry_date) {
            $this->update(['status' => self::STATUS_VALID]);
            return;
        }

        $today = Carbon::today();
        $expiry = Carbon::parse($this->expiry_date);

        if ($expiry->lt($today)) {
            $this->update(['status' => self::STATUS_EXPIRED]);
        } elseif ($expiry->lte($today->copy()->addDays(self::EXPIRING_SOON_DAYS))) {
            $this->update(['status' => self::STATUS_EXPIRING_SOON]);
        } else {
            $this->update(['status' => self::STATUS_VALID]);
        }
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expiry_date && Carbon::parse($this->expiry_date)->lt(Carbon::today()));
    }
}
