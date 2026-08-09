<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'branch_manager', 'fleet_manager']);
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $user->branch_id === $branch->id;
        }

        if ($user->isStaff() || $user->isFleetManager()) {
            return $user->branch_id === $branch->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->isAdmin();
    }

    public function manageStaff(User $user): bool
    {
        return in_array($user->role, ['admin', 'branch_manager']);
    }
}
