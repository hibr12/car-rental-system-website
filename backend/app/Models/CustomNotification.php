<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (CustomNotification $notification) {
            if (empty($notification->id)) {
                $notification->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_type',
        'related_id',
        'read_at',
        'notifiable_type',
        'notifiable_id',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function getRelatedUrlAttribute(): ?string
    {
        if (!$this->related_type || !$this->related_id) {
            return null;
        }

        $modelClass = $this->related_type;
        $model = new $modelClass();

        return match(class_basename($modelClass)) {
            'Booking' => "/bookings/{$this->related_id}",
            'Vehicle' => "/vehicles/{$this->related_id}",
            'Payment' => "/payments/{$this->related_id}",
            'Maintenance' => "/maintenance/{$this->related_id}",
            default => null,
        };
    }
}
