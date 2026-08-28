<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BranchManagerProvisioningService
{
    public function __construct(
        private AuditLogService $auditLog
    ) {}

    /**
     * Create or update the branch manager account and link it to the branch.
     */
    public function provision(Branch $branch, array $options = [], ?User $actor = null): User
    {
        $codeSlug = Str::slug(strtolower($branch->code), '.');

        $email = $options['email']
            ?? $this->defaultManagerEmail($branch);

        $manager = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $options['name'] ?? "{$branch->name} Manager",
                'password' => isset($options['password'])
                    ? Hash::make($options['password'])
                    : Hash::make('password'),
                'phone' => $options['phone'] ?? $branch->phone,
                'role' => User::ROLE_BRANCH_MANAGER,
                'branch_id' => $branch->id,
            ]
        );

        $branch->update(['manager_id' => $manager->id]);

        if ($actor) {
            $this->auditLog->log(
                $actor,
                'branch_manager_provisioned',
                'branch',
                $branch->id,
                null,
                ['manager_id' => $manager->id, 'manager_email' => $manager->email],
                null,
                $branch->id
            );
        }

        return $manager->fresh();
    }

    /**
     * Ensure every branch has a manager account.
     *
     * @return array<int, User> managers created or updated
     */
    public function provisionAllMissing(): array
    {
        $provisioned = [];

        Branch::query()
            ->where(function ($q) {
                $q->whereNull('manager_id')
                    ->orWhereDoesntHave('manager');
            })
            ->each(function (Branch $branch) use (&$provisioned) {
                $provisioned[] = $this->provision($branch);
            });

        return $provisioned;
    }

    public function assignManager(Branch $branch, User $user, ?User $actor = null): User
    {
        if ($branch->manager_id && $branch->manager_id !== $user->id) {
            User::where('id', $branch->manager_id)
                ->where('role', User::ROLE_BRANCH_MANAGER)
                ->where('branch_id', $branch->id)
                ->update(['role' => User::ROLE_BRANCH_STAFF]);
        }

        $user->update([
            'branch_id' => $branch->id,
            'role' => User::ROLE_BRANCH_MANAGER,
        ]);

        $branch->update(['manager_id' => $user->id]);

        if ($actor) {
            $this->auditLog->log(
                $actor,
                'branch_manager_assigned',
                'branch',
                $branch->id,
                null,
                ['manager_id' => $user->id],
                null,
                $branch->id
            );
        }

        return $user->fresh();
    }

    public function defaultManagerEmail(Branch $branch): string
    {
        $slug = Str::slug(strtolower($branch->code), '.');

        return "{$slug}.manager@apexrentals.com";
    }
}
