<?php

namespace Tests\Support;

use App\Models\DriverLicense;
use App\Models\User;

/**
 * Test helper — gives a customer a pre-verified driver's license so that
 * booking-creation tests are not blocked by the license eligibility check.
 */
trait HasVerifiedLicense
{
    protected function giveVerifiedLicense(User $customer): DriverLicense
    {
        return DriverLicense::create([
            'user_id'             => $customer->id,
            'license_number'      => 'TEST-' . rand(10000, 99999),
            'full_name'           => $customer->name,
            'license_category'    => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date'          => now()->subYear()->toDateString(),
            'expiry_date'         => now()->addYears(4)->toDateString(),
            'issuing_authority'   => 'Test Authority',
            'front_document_path' => null,
            'back_document_path'  => null,
            'status'              => DriverLicense::STATUS_VERIFIED,
            'verified_at'         => now(),
            'submitted_at'        => now()->subDay(),
        ]);
    }
}
