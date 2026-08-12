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

        return $user->id === $booking->user_id
            && in_array($booking->normalizeStatus(), Booking::CANCELLABLE_STATUSES, true);
    }

    public function confirm(User $user, Booking $booking): bool
    {
        $status = $booking->normalizeStatus();

        if ($user->isBranchManager()) {
            return (int) $user->branch_id === (int) $booking->branch_id
                && $booking->branch_approval_status === Booking::APPROVAL_PENDING
                && in_array($status, [
                    Booking::STATUS_PENDING_BRANCH_APPROVAL,
                    Booking::STATUS_PAYMENT_VERIFIED,
                    Booking::STATUS_PENDING,
                    Booking::STATUS_BRANCH_REVIEW,
                ], true);
        }

        if ($user->isAdmin()) {
            if ($booking->branch_approval_status === Booking::APPROVAL_PENDING
                && in_array($status, [
                    Booking::STATUS_PENDING_BRANCH_APPROVAL,
                    Booking::STATUS_PAYMENT_VERIFIED,
                    Booking::STATUS_PENDING,
                    Booking::STATUS_BRANCH_REVIEW,
                ], true)) {
                return true;
            }

            return $booking->admin_approval_status === Booking::APPROVAL_PENDING
                && in_array($status, [
                    Booking::STATUS_PENDING_ADMIN_APPROVAL,
                    Booking::STATUS_PENDING_BRANCH_APPROVAL,
                ], true);
        }

        return false;
    }

    public function reject(User $user, Booking $booking): bool
    {
        $status = $booking->normalizeStatus();
        $rejectable = [
            Booking::STATUS_PENDING_BRANCH_APPROVAL,
            Booking::STATUS_PENDING_ADMIN_APPROVAL,
            Booking::STATUS_PAYMENT_VERIFIED,
            Booking::STATUS_PENDING_PAYMENT,
            Booking::STATUS_PENDING,
            Booking::STATUS_BRANCH_REVIEW,
        ];

        if ($user->isBranchManager()) {
            return (int) $user->branch_id === (int) $booking->branch_id
                && in_array($status, $rejectable, true);
        }

        if ($user->isAdmin()) {
            return in_array($status, $rejectable, true);
        }

        return false;
    }

    public function pickup(User $user, ?Booking $booking = null): bool
    {
        if (!($user->isAdmin() || $user->isBranchManager() || $user->isStaff())) {
            return false;
        }

        if ($booking && !$user->isAdmin()) {
            return (int) $user->branch_id === (int) $booking->branch_id;
        }

        return true;
    }

    public function returnVehicle(User $user, ?Booking $booking = null): bool
    {
        if (!($user->isAdmin() || $user->isBranchManager() || $user->isStaff())) {
            return false;
        }

        if ($booking && !$user->isAdmin()) {
            return (int) $user->branch_id === (int) $booking->branch_id;
        }

        return true;
    }

    public function preparePickup(User $user, Booking $booking): bool
    {
        return $this->pickup($user, $booking);
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
