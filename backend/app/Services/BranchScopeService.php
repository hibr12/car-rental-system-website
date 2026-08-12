<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centralized branch-scoping helpers for multi-branch authorization.
 *
 * Branch managers and staff are restricted to their assigned branch_id.
 * Admins and fleet managers have company-wide access for fleet operations.
 */
class BranchScopeService
{
    public function isBranchScoped(User $user): bool
    {
        return $user->isBranchManager() || $user->isStaff();
    }

    public function hasCompanyWideAccess(User $user): bool
    {
        return $user->isAdmin() || $user->isFleetManager();
    }

    /**
     * Apply branch filter to a query for branch-scoped users.
     */
    public function scopeQuery(Builder $query, User $user, string $column = 'branch_id'): ?int
    {
        if ($this->hasCompanyWideAccess($user)) {
            return null;
        }

        if ($this->isBranchScoped($user) && $user->branch_id) {
            $query->where($column, $user->branch_id);

            return (int) $user->branch_id;
        }

        return null;
    }

    /**
     * Scope query via a vehicle relationship (e.g. maintenance on vehicles).
     */
    public function scopeQueryViaVehicle(Builder $query, User $user): ?int
    {
        if ($this->hasCompanyWideAccess($user)) {
            return null;
        }

        if ($this->isBranchScoped($user) && $user->branch_id) {
            $branchId = (int) $user->branch_id;
            $query->whereHas('vehicle', fn ($q) => $q->where('branch_id', $branchId));

            return $branchId;
        }

        return null;
    }

    /**
     * Validate branch_id from request — deny if branch-scoped user tries another branch.
     */
    public function resolveBranchFilter(User $user, ?int $requestedBranchId): ?int
    {
        if ($this->hasCompanyWideAccess($user)) {
            return $requestedBranchId;
        }

        if ($this->isBranchScoped($user)) {
            if ($requestedBranchId !== null && (int) $requestedBranchId !== (int) $user->branch_id) {
                abort(403, 'Access denied. You do not have permission to access this branch.');
            }

            return $user->branch_id ? (int) $user->branch_id : null;
        }

        return $requestedBranchId;
    }

    /**
     * Assert user can access a specific branch-owned resource.
     */
    public function assertCanAccessBranch(User $user, ?int $resourceBranchId): void
    {
        if ($resourceBranchId === null) {
            return;
        }

        if ($user->isAdmin() || $user->isFleetManager() || $user->isCustomer()) {
            return;
        }

        if (!$user->hasBranchAccess($resourceBranchId)) {
            abort(403, 'Access denied. You do not have permission to access this resource.');
        }
    }

    /**
     * Force branch_id to authenticated user's branch for create operations.
     */
    public function forceOwnBranchId(User $user, array $data): array
    {
        if ($this->isBranchScoped($user) && $user->branch_id) {
            $data['branch_id'] = $user->branch_id;
        }

        return $data;
    }

    /**
     * Remove branch_id from update payloads — branch moves require transfer workflow.
     */
    public function stripBranchId(User $user, array $data): array
    {
        if ($this->isBranchScoped($user)) {
            unset($data['branch_id']);
        }

        return $data;
    }

    /**
     * Validate transfer-related branch filters for branch-scoped users.
     */
    public function assertTransferBranchFilter(User $user, ?int $branchId): void
    {
        if ($branchId === null) {
            return;
        }

        if ($this->hasCompanyWideAccess($user)) {
            return;
        }

        if ($this->isBranchScoped($user) && (int) $branchId !== (int) $user->branch_id) {
            abort(403, 'Access denied. You do not have permission to access this branch.');
        }
    }
}
