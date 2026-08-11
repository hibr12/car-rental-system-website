<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN    = 'super_admin';
    public const ROLE_COMPANY_ADMIN  = 'admin';
    public const ROLE_BRANCH_MANAGER = 'branch_manager';
    public const ROLE_FLEET_MANAGER  = 'fleet_manager';
    public const ROLE_BRANCH_STAFF   = 'staff';
    public const ROLE_CUSTOMER       = 'customer';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_COMPANY_ADMIN,
        self::ROLE_BRANCH_MANAGER,
        self::ROLE_FLEET_MANAGER,
        self::ROLE_BRANCH_STAFF,
        self::ROLE_CUSTOMER,
    ];

    public const OPERATIONAL_ROLES = [
        'branch_manager' => 'Branch Manager',
        'rental_agent' => 'Rental Agent',
        'fleet_staff' => 'Fleet Staff',
        'inspection_staff' => 'Inspection Staff',
        'maintenance_staff' => 'Maintenance Staff',
        'finance_staff' => 'Finance Staff',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_photo',
        'role',
        'branch_id',
        'operational_role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'branch_id'         => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function managedBranch(): HasOne
    {
        return $this->hasOne(Branch::class, 'manager_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'created_by');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_COMPANY_ADMIN]);
    }

    public function isBranchManager(): bool
    {
        return $this->role === self::ROLE_BRANCH_MANAGER;
    }

    public function isFleetManager(): bool
    {
        return $this->role === self::ROLE_FLEET_MANAGER;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_BRANCH_STAFF;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isManagement(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_COMPANY_ADMIN,
            self::ROLE_BRANCH_MANAGER,
            self::ROLE_FLEET_MANAGER,
            self::ROLE_BRANCH_STAFF,
        ]);
    }

    public function hasBranchAccess($branchId = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->branch_id === null) {
            return false;
        }

        if ($branchId === null) {
            return true;
        }

        return (int) $this->branch_id === (int) $branchId;
    }

    public function scopeInBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeNotCustomer($query)
    {
        return $query->whereIn('role', [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_COMPANY_ADMIN,
            self::ROLE_BRANCH_MANAGER,
            self::ROLE_FLEET_MANAGER,
            self::ROLE_BRANCH_STAFF,
        ]);
    }

    public static function factory(): UserFactory
    {
        return new UserFactory();
    }
}
