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
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_REFUND_PENDING = 'refund_pending';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_DISPUTED = 'disputed';

    public const STATUSES = [
        self::STATUS_UNPAID,
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_CASH_PENDING,
        self::STATUS_PAID,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
        self::STATUS_INVALID,
        self::STATUS_REFUND_PENDING,
        self::STATUS_PARTIALLY_REFUNDED,
        self::STATUS_REFUNDED,
        self::STATUS_DISPUTED,
    ];

    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CARD = 'card';
    public const METHOD_ONLINE_PAYMENT = 'online_payment';

    public const GATEWAY_CHAPA = 'chapa';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_VERIFYING = 'verifying';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_AMOUNT_MISMATCH = 'amount_mismatch';
    public const VERIFICATION_CURRENCY_MISMATCH = 'currency_mismatch';
    public const VERIFICATION_REFERENCE_MISMATCH = 'reference_mismatch';
    public const VERIFICATION_GATEWAY_FAILED = 'gateway_failed';
    public const VERIFICATION_GATEWAY_PENDING = 'gateway_pending';
    public const VERIFICATION_ERROR = 'verification_error';
    public const VERIFICATION_MANUALLY_CONFIRMED = 'manually_confirmed';

    public const MISMATCH_UNDERPAYMENT = 'SHORT_PAYMENT';
    public const MISMATCH_OVERPAYMENT = 'OVERPAYMENT';

    public const PAYMENT_METHODS = [
        self::METHOD_CASH,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_CARD,
        self::METHOD_ONLINE_PAYMENT,
    ];

    /** Statuses that count as settled for a booking */
    public const SETTLED_STATUSES = [
        self::STATUS_PAID,
        self::STATUS_REFUND_PENDING,
        self::STATUS_PARTIALLY_REFUNDED,
        self::STATUS_REFUNDED,
    ];

    protected $fillable = [
        'booking_id',
        'attempt_number',
        'user_id',
        'branch_id',
        'amount',
        'expected_amount',
        'paid_amount',
        'amount_received',
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
        'mismatch_reason',
        'receipt_number',
        'confirmed_by',
        'confirmed_at',
        'refund_amount',
        'refunded_at',
        'idempotency_key',
        'is_archived',
        'archived_at',
        'archived_by',
        'archive_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'amount_received' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'branch_id' => 'integer',
            'attempt_number' => 'integer',
            'gateway_response' => 'array',
        ];
    }

    public function expectedAmount(): string
    {
        return number_format((float) ($this->expected_amount ?? $this->amount), 2, '.', '');
    }

    public function isVerified(): bool
    {
        return in_array($this->verification_status, [
            self::VERIFICATION_VERIFIED,
            self::VERIFICATION_MANUALLY_CONFIRMED,
        ], true);
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_PAID && $this->isVerified();
    }

    public function isGatewayPayment(): bool
    {
        return $this->payment_method === self::METHOD_ONLINE_PAYMENT;
    }

    public function isMismatch(): bool
    {
        return in_array($this->verification_status, [
            self::VERIFICATION_AMOUNT_MISMATCH,
            self::VERIFICATION_CURRENCY_MISMATCH,
            self::VERIFICATION_REFERENCE_MISMATCH,
        ], true) || $this->status === self::STATUS_INVALID;
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

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
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
        return $query->whereIn('status', [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED]);
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
