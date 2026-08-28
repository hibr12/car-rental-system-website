<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAnyAdmin(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchManager();
    }

    public function view(User $user, Review $review): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBranchManager() && $review->branch_id === $user->branch_id) {
            return true;
        }

        if ($user->id === $review->user_id) {
            return true;
        }

        return $review->status === Review::STATUS_PUBLISHED;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, Review $review): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        return $user->id === $review->user_id && $review->isEditableByCustomer();
    }

    public function moderate(User $user, Review $review): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isBranchManager() && $review->branch_id === $user->branch_id;
    }

    public function respond(User $user, Review $review): bool
    {
        return $this->moderate($user, $review);
    }

    public function archive(User $user, Review $review): bool
    {
        return $this->moderate($user, $review);
    }

    /** @deprecated Use archive */
    public function delete(User $user, Review $review): bool
    {
        return $this->archive($user, $review);
    }
}
