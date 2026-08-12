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
        if ($user->isAdmin() || $user->isFleetManager()) {
            return true;
        }

        if ($user->isBranchManager() || $user->isStaff()) {
            return $user->branch_id === $vehicle->branch_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isFleetManager();
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->isAdmin() || $user->isFleetManager();
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->isAdmin();
    }
}
