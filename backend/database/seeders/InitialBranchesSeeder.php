<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class InitialBranchesSeeder extends Seeder
{
    /**
     * Idempotent seed: ensures company + three core branches exist
     * and assigns unassigned vehicles across them.
     */
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['code' => 'ABAY'],
            [
                'name'      => 'Abay Car Rentals',
                'address'   => 'Bahir Dar, Amhara Region, Ethiopia',
                'phone'     => '+251 92 667 3294',
                'email'     => '12hibr13@gmail.com',
                'is_active' => true,
            ]
        );

        // Normalize duplicate Kazanchis branches (legacy KAZANCHIS + new KAZ)
        $legacyKaz = Branch::where('code', 'KAZANCHIS')->first();
        $newKaz = Branch::where('code', 'KAZ')->first();

        if ($legacyKaz && $newKaz && $legacyKaz->id !== $newKaz->id) {
            if ($newKaz->vehicles()->count() === 0 && $newKaz->bookings()->count() === 0) {
                $newKaz->delete();
            } else {
                Vehicle::where('branch_id', $legacyKaz->id)->update(['branch_id' => $newKaz->id]);
                Booking::where('branch_id', $legacyKaz->id)->update(['branch_id' => $newKaz->id]);
                Payment::where('branch_id', $legacyKaz->id)->update(['branch_id' => $newKaz->id]);
                User::where('branch_id', $legacyKaz->id)->update(['branch_id' => $newKaz->id]);
                $legacyKaz->delete();
            }
        }

        if ($legacyKaz && Branch::where('code', 'KAZ')->doesntExist()) {
            $legacyKaz->update(['code' => 'KAZ']);
        }

        $branchesData = [
            [
                'name'    => 'Main Branch',
                'code'    => 'MAIN',
                'address' => 'Tana Road, Bahir Dar',
                'city'    => 'Bahir Dar',
                'phone'   => '+251 92 667 3294',
                'email'   => 'main@abaycarrentals.com',
                'status'  => 'active',
            ],
            [
                'name'    => 'Airport Branch',
                'code'    => 'AIRPORT',
                'address' => 'Bahir Dar Airport, Bahir Dar',
                'city'    => 'Bahir Dar',
                'phone'   => '+251 92 667 3295',
                'email'   => 'airport@abaycarrentals.com',
                'status'  => 'active',
            ],
            [
                'name'    => 'University Branch',
                'code'    => 'UNIV',
                'address' => 'Bahir Dar University Road, Bahir Dar',
                'city'    => 'Bahir Dar',
                'phone'   => '+251 92 667 3296',
                'email'   => 'university@abaycarrentals.com',
                'status'  => 'active',
            ],
        ];

        $branchIds = [];
        foreach ($branchesData as $branchData) {
            $branch = Branch::updateOrCreate(
                ['code' => $branchData['code']],
                array_merge($branchData, ['company_id' => $company->id])
            );
            $branchIds[] = $branch->id;
        }

        Branch::where('code', 'KAZANCHIS')->delete();

        if (count($branchIds) < 3) {
            return;
        }

        $branchesByName = Branch::all()->keyBy(fn (Branch $b) => strtolower($b->name));

        Vehicle::whereNull('branch_id')->orderBy('id')->each(function (Vehicle $vehicle) use ($branchIds, $branchesByName) {
            $assignedBranchId = null;

            if ($vehicle->location) {
                $locationKey = strtolower(trim($vehicle->location));
                if ($branchesByName->has($locationKey)) {
                    $assignedBranchId = $branchesByName->get($locationKey)->id;
                } else {
                    foreach ($branchesByName as $branch) {
                        $shortName = strtolower(str_replace(' Branch', '', $branch->name));
                        if (str_contains($locationKey, $shortName)) {
                            $assignedBranchId = $branch->id;
                            break;
                        }
                    }
                }
            }

            if (!$assignedBranchId) {
                $assignedBranchId = $branchIds[($vehicle->id - 1) % count($branchIds)];
            }

            $vehicle->update(['branch_id' => $assignedBranchId]);
        });
    }
}
