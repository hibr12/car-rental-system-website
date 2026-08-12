<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        User::where('email', 'fleet@carrental.com')->update([
            'role' => User::ROLE_FLEET_MANAGER,
            'branch_id' => null,
        ]);

        $branches = [
            'BOLE' => [
                'email' => 'bole.manager@apexrentals.com',
                'name' => 'Bole Branch Manager',
                'phone' => '+251 11 111 0000',
            ],
            'CMC' => [
                'email' => 'cmc.manager@apexrentals.com',
                'name' => 'CMC Branch Manager',
                'phone' => '+251 11 222 0000',
            ],
            'KAZ' => [
                'email' => 'kazanchis.manager@apexrentals.com',
                'name' => 'Kazanchis Branch Manager',
                'phone' => '+251 11 444 0000',
            ],
        ];

        foreach ($branches as $code => $data) {
            $branch = Branch::where('code', $code)->first();

            if (!$branch) {
                continue;
            }

            $manager = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'phone' => $data['phone'],
                    'role' => User::ROLE_BRANCH_MANAGER,
                    'branch_id' => $branch->id,
                ]
            );

            $branch->update(['manager_id' => $manager->id]);
        }
    }

    public function down(): void
    {
        // Non-destructive: account fixes are not reverted.
    }
};
