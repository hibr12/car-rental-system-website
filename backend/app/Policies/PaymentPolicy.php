<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'fleet_manager']);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isAdmin() || $user->isStaff() || $user->isFleetManager()) {
            return true;
        }

        return $user->id === $payment->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }
}