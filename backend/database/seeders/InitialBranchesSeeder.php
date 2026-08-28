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
            ['code' => 'APEX'],
            [
                'name'      => 'Apex Rentals',
                'address'   => '123 Main Street, Addis Ababa, Ethiopia',
                'phone'     => '+251 11 123 4567',
                'email'     => 'info@apexrentals.com',
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
                'name'    => 'Bole Branch',
                'code'    => 'BOLE',
                'address' => 'Bole Road, Bole Subcity, Addis Ababa',
                'city'    => 'Addis Ababa',
                'phone'   => '+251 11 111 1111',
                'email'   => 'bole@apexrentals.com',
                'status'  => 'active',
            ],
            [
                'name'    => 'Kazanchis Branch',
                'code'    => 'KAZ',
                'address' => 'Kazanchis, Kirkos Subcity, Addis Ababa',
                'city'    => 'Addis Ababa',
                'phone'   => '+251 11 444 4444',
                'email'   => 'kazanchis@apexrentals.com',
                'status'  => 'active',
            ],
            [
                'name'    => 'CMC Branch',
                'code'    => 'CMC',
                'address' => 'CMC Michael, Yeka Subcity, Addis Ababa',
                'city'    => 'Addis Ababa',
                'phone'   => '+251 11 222 2222',
                'email'   => 'cmc@apexrentals.com',
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
