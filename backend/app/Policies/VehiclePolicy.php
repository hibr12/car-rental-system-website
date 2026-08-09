<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'fleet_manager']);
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        if (in_array($user->role, ['admin', 'fleet_manager'])) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $user->branch_id === $vehicle->branch_id;
        }

        return false;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->role === 'admin';
    }

    public function manageAvailability(User $user): bool
    {
        return in_array($user->role, ['admin', 'fleet_manager', 'branch_manager']);
    }
}
