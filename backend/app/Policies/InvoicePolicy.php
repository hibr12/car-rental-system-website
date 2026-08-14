<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'fleet_manager', 'branch_manager', 'customer']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin() || $user->isStaff() || $user->isFleetManager()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $user->branch_id === $invoice->booking->branch_id;
        }

        return $user->id === $invoice->user_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'branch_manager']);
    }

    public function download(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin() || $user->isStaff()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $user->branch_id === $invoice->booking->branch_id;
        }

        return $user->id === $invoice->user_id;
    }
}
