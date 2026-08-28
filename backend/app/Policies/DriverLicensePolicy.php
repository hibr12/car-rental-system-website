<?php

namespace App\Policies;

use App\Models\DriverLicense;
use App\Models\User;

class DriverLicensePolicy
{
    /** List all licenses (admin/staff only). */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchManager() || $user->isStaff();
    }

    /** View a specific license. */
    public function view(User $user, DriverLicense $license): bool
    {
        if ($user->isAdmin() || $user->isBranchManager() || $user->isStaff()) {
            return true;
        }

        return $user->isCustomer() && (int) $license->user_id === (int) $user->id;
    }

    /** Submit / create a license. */
    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    /** Update / replace documents. */
    public function update(User $user, DriverLicense $license): bool
    {
        return $user->isCustomer() && (int) $license->user_id === (int) $user->id;
    }

    /** Approve a license. */
    public function approve(User $user, DriverLicense $license): bool
    {
        return $user->isAdmin() || $user->isBranchManager() || $user->isStaff();
    }

    /** Reject a license. */
    public function reject(User $user, DriverLicense $license): bool
    {
        return $user->isAdmin() || $user->isBranchManager() || $user->isStaff();
    }

    /** View secure documents. */
    public function viewDocument(User $user, DriverLicense $license): bool
    {
        if ($user->isAdmin() || $user->isBranchManager() || $user->isStaff()) {
            return true;
        }

        return $user->isCustomer() && (int) $license->user_id === (int) $user->id;
    }
}
