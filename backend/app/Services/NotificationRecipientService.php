<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationRecipientService
{
    /**
     * Company admins plus branch managers who should receive operational alerts.
     * When $branchId is provided, branch managers are limited to that branch.
     */
    public function adminsAndBranchManagers(?int $branchId = null): Collection
    {
        $admins = User::query()
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_COMPANY_ADMIN])
            ->get();

        $managerQuery = User::query()->where('role', User::ROLE_BRANCH_MANAGER);

        if ($branchId !== null) {
            $managerQuery->where('branch_id', $branchId);
        }

        return $admins->merge($managerQuery->get())->unique('id')->values();
    }
}
