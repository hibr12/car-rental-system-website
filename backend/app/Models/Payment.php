<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_CASH_PENDING = 'cash_pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_UNPAID,
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_CASH_PENDING,
        self::STATUS_PAID,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
    ];

    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CARD = 'card';
    public const METHOD_ONLINE_PAYMENT = 'online_payment';

    public const GATEWAY_CHAPA = 'chapa';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_MANUALLY_CONFIRMED = 'manually_confirmed';

    public const PAYMENT_METHODS = [
        self::METHOD_CASH,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_CARD,
        self::METHOD_ONLINE_PAYMENT,
    ];

    protected $fillable = [
        'booking_id',
        'user_id',
        'branch_id',
        'amount',
        'currency',
        'payment_method',
        'transaction_reference',
        'gateway_reference',
        'gateway',
        'gateway_status',
        'gateway_response',
        'status',
        'verification_status',
        'paid_at',
        'verified_at',
        'verified_by',
        'verification_source',
        'failure_reason',
        'receipt_number',
        'confirmed_by',
        'confirmed_at',
        'is_archived',
        'archived_at',
        'archived_by',
        'archive_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'branch_id' => 'integer',
            'gateway_response' => 'array',
        ];
    }

    public function isVerified(): bool
    {
        return in_array($this->verification_status, [
            self::VERIFICATION_VERIFIED,
            self::VERIFICATION_MANUALLY_CONFIRMED,
        ], true);
    }

    public function isGatewayPayment(): bool
    {
        return $this->payment_method === self::METHOD_ONLINE_PAYMENT;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    public function scopeInBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActiveRecords($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }
}
