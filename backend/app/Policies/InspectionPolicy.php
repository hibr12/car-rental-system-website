<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'fleet_manager', 'branch_manager']);
    }

    public function view(User $user, Inspection $inspection): bool
    {
        if ($user->isAdmin() || $user->isStaff() || $user->isFleetManager()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $user->branch_id === $inspection->vehicle->branch_id;
        }

        return $user->id === $inspection->booking->user_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'branch_manager']);
    }

    public function update(User $user, Inspection $inspection): bool
    {
        return in_array($user->role, ['admin', 'staff', 'branch_manager']);
    }
}
