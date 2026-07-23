<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'fleet_manager']);
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($user->isAdmin() || $user->isStaff() || $user->isFleetManager()) {
            return true;
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

        return $user->id === $booking->user_id && in_array($booking->status, ['pending', 'confirmed']);
    }

    public function confirm(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }

    public function reject(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }

    public function pickup(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }

    public function returnVehicle(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }

    public function manageAll(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'fleet_manager']);
    }
}