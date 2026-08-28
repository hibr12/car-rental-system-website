<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchManager() || $user->isStaff() || $user->isFleetManager();
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isAdmin() || $user->isFleetManager()) {
            return true;
        }

        if ($user->isBranchManager() || $user->isStaff()) {
            return (int) $user->branch_id === (int) $payment->branch_id;
        }

        return (int) $user->id === (int) $payment->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, Payment $payment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager() || $user->isStaff()) {
            return (int) $user->branch_id === (int) $payment->branch_id;
        }

        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        // Financial records must never be permanently deleted through the UI.
        return false;
    }

    public function archive(User $user, Payment $payment): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        return !in_array($payment->status, [
            Payment::STATUS_PAID,
            Payment::STATUS_REFUNDED,
            Payment::STATUS_PARTIALLY_REFUNDED,
            Payment::STATUS_REFUND_PENDING,
            Payment::STATUS_CASH_PENDING,
            Payment::STATUS_PROCESSING,
            Payment::STATUS_DISPUTED,
        ], true);
    }

    public function archiveAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    public function confirmCash(User $user, Payment $payment): bool
    {
        if ($payment->payment_method !== Payment::METHOD_CASH) {
            return false;
        }

        if ($payment->status !== Payment::STATUS_CASH_PENDING) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager() || $user->isStaff()) {
            return (int) $user->branch_id === (int) $payment->branch_id;
        }

        return false;
    }
}
