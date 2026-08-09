<?php

namespace App\Policies;

use App\Models\Maintenance;
use App\Models\User;

class MaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'fleet_manager', 'staff', 'branch_manager']);
    }

    public function view(User $user, Maintenance $maintenance): bool
    {
        return in_array($user->role, ['admin', 'fleet_manager', 'staff', 'branch_manager']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'fleet_manager', 'branch_manager']);
    }

    public function update(User $user, Maintenance $maintenance): bool
    {
        return in_array($user->role, ['admin', 'fleet_manager']);
    }

    public function delete(User $user, Maintenance $maintenance): bool
    {
        return in_array($user->role, ['admin', 'fleet_manager']);
    }
}
