<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'fleet_manager', 'branch_manager']);
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($user->isAdmin() || $user->isStaff() || $user->isFleetManager()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $user->branch_id === $booking->branch_id || $user->id === $booking->user_id;
        }

        return $user->id === $booking->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->isAdmin() || $user->isStaff()) {
            return true;
        }

        if ($user->isBranchManager()) {
            return $user->branch_id === $booking->branch_id && in_array($booking->status, ['pending', 'confirmed']);
        }

        return $user->id === $booking->user_id && in_array($booking->status, ['pending', 'confirmed']);
    }

    public function confirm(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'branch_manager']);
    }

    public function reject(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'branch_manager']);
    }

    public function pickup(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'branch_manager']);
    }

    public function returnVehicle(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'branch_manager']);
    }

    public function manageAll(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'fleet_manager', 'branch_manager']);
    }

    public function approve(User $user): bool
    {
        return in_array($user->role, ['admin', 'branch_manager']);
    }
}