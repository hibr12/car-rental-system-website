<?php

namespace App\Policies;

use App\Models\Maintenance;
use App\Models\User;

class MaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'fleet_manager', 'branch_manager', 'staff']);
    }

    public function view(User $user, Maintenance $maintenance): bool
    {
        if ($user->isAdmin() || $user->isFleetManager()) {
            return true;
        }

        if ($user->isBranchManager() || $user->isStaff()) {
            return (int) $user->branch_id === (int) $maintenance->branch_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'fleet_manager']);
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
