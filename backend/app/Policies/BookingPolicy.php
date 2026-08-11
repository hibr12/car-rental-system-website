<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchManager() || $user->isStaff() || $user->isFleetManager();
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager() || $user->isStaff()) {
            return (int) $user->branch_id === (int) $booking->branch_id;
        }

        if ($user->isFleetManager()) {
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

        if ($user->isBranchManager()) {
            return (int) $user->branch_id === (int) $booking->branch_id;
        }

        return $user->id === $booking->user_id && in_array($booking->status, ['pending', 'confirmed']);
    }

    public function confirm(User $user, Booking $booking): bool
    {
        if ($user->isBranchManager()) {
            return (int) $user->branch_id === (int) $booking->branch_id
                && $booking->branch_approval_status === Booking::APPROVAL_PENDING;
        }

        if ($user->isAdmin()) {
            return $booking->branch_approval_status === Booking::APPROVAL_APPROVED
                && $booking->admin_approval_status === Booking::APPROVAL_PENDING;
        }

        return false;
    }

    public function reject(User $user, Booking $booking): bool
    {
        if ($user->isBranchManager()) {
            return (int) $user->branch_id === (int) $booking->branch_id
                && $booking->status === Booking::STATUS_PENDING;
        }

        if ($user->isAdmin()) {
            return $booking->status === Booking::STATUS_PENDING;
        }

        return $user->isStaff();
    }

    public function pickup(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchManager() || $user->isStaff();
    }

    public function returnVehicle(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchManager() || $user->isStaff();
    }

    public function manageAll(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchManager() || $user->isStaff() || $user->isFleetManager();
    }

    public function archive(User $user, Booking $booking): bool
    {
        return $user->isAdmin();
    }

    public function archiveAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Booking $booking): bool
    {
        return false;
    }
}
