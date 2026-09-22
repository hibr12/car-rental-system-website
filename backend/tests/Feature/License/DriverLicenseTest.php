<?php

namespace Tests\Feature\License;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DriverLicense;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\AdminLicenseSubmitted;
use App\Notifications\LicenseApproved;
use App\Notifications\LicenseSubmitted;
use App\Services\DriverLicenseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Support\HasVerifiedLicense;
use Tests\TestCase;

class DriverLicenseTest extends TestCase
{
    use RefreshDatabase, HasVerifiedLicense;

    private User $customer;
    private User $otherCustomer;
    private User $admin;
    private User $manager;
    private User $staff;
    private Branch $branch;
    private Vehicle $vehicle;
    private DriverLicenseService $licenseService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->branch = Branch::factory()->create(['code' => 'LIC-TST']);
        $category = Category::factory()->create();

        $this->customer = User::factory()->customer()->create();
        $this->otherCustomer = User::factory()->customer()->create();
        $this->admin = User::factory()->admin()->create();
        $this->manager = User::factory()->branchManager()->create(['branch_id' => $this->branch->id]);
        $this->staff = User::factory()->create(['role' => 'staff', 'branch_id' => $this->branch->id]);

        $this->vehicle = Vehicle::factory()->available()->create([
            'category_id' => $category->id,
            'branch_id' => $this->branch->id,
            'rental_price_per_day' => 200,
            'required_license_category' => DriverLicense::CATEGORY_AUTOMOBILE,
            'requires_license' => true,
        ]);

        $this->licenseService = app(DriverLicenseService::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Customer upload
    // ─────────────────────────────────────────────────────────────────────────

    public function test_customer_can_submit_license(): void
    {
        $token = $this->customer->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/license', [
                'license_number'   => 'ETH-12345678',
                'full_name'        => $this->customer->name,
                'license_category' => 'automobile',
                'issue_date'       => now()->subYear()->toDateString(),
                'expiry_date'      => now()->addYears(4)->toDateString(),
                'front_document'   => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
                'back_document'    => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', DriverLicense::STATUS_PENDING_REVIEW);

        $this->assertDatabaseHas('driver_licenses', [
            'user_id' => $this->customer->id,
            'status'  => DriverLicense::STATUS_PENDING_REVIEW,
        ]);
    }

    public function test_customer_can_view_own_license(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/license')
            ->assertOk()
            ->assertJsonPath('data.id', $license->id);
    }

    public function test_customer_cannot_view_another_customers_license(): void
    {
        $this->createPendingLicense($this->otherCustomer);
        $token = $this->customer->createToken('t')->plainTextToken;

        // My license endpoint only returns own license — the other customer has no license for $customer
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/license');

        $response->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_missing_front_image_is_rejected(): void
    {
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/license', [
                'license_number'   => 'ETH-12345678',
                'full_name'        => $this->customer->name,
                'license_category' => 'automobile',
                'issue_date'       => now()->subYear()->toDateString(),
                'expiry_date'      => now()->addYears(4)->toDateString(),
                // front_document missing
                'back_document'    => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            ])
            ->assertStatus(422);
    }

    public function test_missing_back_image_is_rejected(): void
    {
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/license', [
                'license_number'   => 'ETH-12345678',
                'full_name'        => $this->customer->name,
                'license_category' => 'automobile',
                'issue_date'       => now()->subYear()->toDateString(),
                'expiry_date'      => now()->addYears(4)->toDateString(),
                'front_document'   => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
                // back_document missing
            ])
            ->assertStatus(422);
    }

    public function test_expired_expiry_date_is_rejected(): void
    {
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/license', [
                'license_number'   => 'ETH-12345678',
                'full_name'        => $this->customer->name,
                'license_category' => 'automobile',
                'issue_date'       => now()->subYears(5)->toDateString(),
                'expiry_date'      => now()->subDay()->toDateString(), // already expired
                'front_document'   => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
                'back_document'    => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            ])
            ->assertStatus(422);
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/license', [
                'license_number'   => 'ETH-12345678',
                'full_name'        => $this->customer->name,
                'license_category' => 'automobile',
                'issue_date'       => now()->subYear()->toDateString(),
                'expiry_date'      => now()->addYears(4)->toDateString(),
                'front_document'   => UploadedFile::fake()->create('front.exe', 100, 'application/exe'),
                'back_document'    => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            ])
            ->assertStatus(422);
    }

    public function test_oversized_file_is_rejected(): void
    {
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/license', [
                'license_number'   => 'ETH-12345678',
                'full_name'        => $this->customer->name,
                'license_category' => 'automobile',
                'issue_date'       => now()->subYear()->toDateString(),
                'expiry_date'      => now()->addYears(4)->toDateString(),
                'front_document'   => UploadedFile::fake()->create('front.jpg', 6000, 'image/jpeg'), // 6MB > 5MB limit
                'back_document'    => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            ])
            ->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Verification workflow
    // ─────────────────────────────────────────────────────────────────────────

    public function test_pending_license_can_be_approved(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $token = $this->admin->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/licenses/{$license->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', DriverLicense::STATUS_VERIFIED);

        $this->assertDatabaseHas('driver_licenses', [
            'id'          => $license->id,
            'status'      => DriverLicense::STATUS_VERIFIED,
            'verified_by' => $this->admin->id,
        ]);
    }

    public function test_pending_license_can_be_rejected(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $token = $this->admin->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/licenses/{$license->id}/reject", [
                'reason' => 'The back image is blurry and unreadable.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', DriverLicense::STATUS_REJECTED);

        $this->assertDatabaseHas('driver_licenses', [
            'id'               => $license->id,
            'status'           => DriverLicense::STATUS_REJECTED,
            'rejection_reason' => 'The back image is blurry and unreadable.',
        ]);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $token = $this->admin->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/licenses/{$license->id}/reject", [
                'reason' => '',
            ])
            ->assertStatus(422);
    }

    public function test_verified_license_has_reviewer_and_timestamp(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $this->licenseService->approve($license, $this->admin);

        $license->refresh();

        $this->assertEquals(DriverLicense::STATUS_VERIFIED, $license->status);
        $this->assertEquals($this->admin->id, $license->verified_by);
        $this->assertNotNull($license->verified_at);
    }

    public function test_already_verified_license_cannot_be_approved_again(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $this->licenseService->approve($license, $this->admin);

        $this->expectException(\InvalidArgumentException::class);
        $this->licenseService->approve($license->fresh(), $this->admin);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Expiry
    // ─────────────────────────────────────────────────────────────────────────

    public function test_expired_license_is_treated_as_expired(): void
    {
        $license = DriverLicense::create([
            'user_id'           => $this->customer->id,
            'license_number'    => 'ETH-EXPIRE',
            'full_name'         => $this->customer->name,
            'license_category'  => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date'        => now()->subYears(5)->toDateString(),
            'expiry_date'       => now()->subDay()->toDateString(), // expired yesterday
            'status'            => DriverLicense::STATUS_VERIFIED,
            'submitted_at'      => now(),
        ]);

        $this->assertEquals(DriverLicense::STATUS_EXPIRED, $license->effectiveStatus());
        $this->assertTrue($license->isExpired());
    }

    public function test_expired_license_cannot_satisfy_booking_eligibility(): void
    {
        DriverLicense::create([
            'user_id'           => $this->customer->id,
            'license_number'    => 'ETH-EXPIRE',
            'full_name'         => $this->customer->name,
            'license_category'  => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date'        => now()->subYears(5)->toDateString(),
            'expiry_date'       => now()->subDay()->toDateString(),
            'status'            => DriverLicense::STATUS_VERIFIED,
            'submitted_at'      => now(),
        ]);

        $result = $this->licenseService->checkEligibility($this->customer, $this->vehicle);

        $this->assertFalse($result['eligible']);
        $this->assertEquals('LICENSE_EXPIRED', $result['code']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Resubmission
    // ─────────────────────────────────────────────────────────────────────────

    public function test_rejected_license_can_be_resubmitted(): void
    {
        $original = $this->createPendingLicense($this->customer);
        $this->licenseService->reject($original, $this->admin, 'Image unclear.');

        $token = $this->customer->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/license', [
                'license_number'   => 'ETH-NEW-999',
                'full_name'        => $this->customer->name,
                'license_category' => 'automobile',
                'issue_date'       => now()->subYear()->toDateString(),
                'expiry_date'      => now()->addYears(4)->toDateString(),
                'front_document'   => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
                'back_document'    => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', DriverLicense::STATUS_PENDING_REVIEW);

        $original->refresh();
        $this->assertEquals(DriverLicense::STATUS_REPLACED, $original->status);
    }

    public function test_old_license_is_retained_on_replacement(): void
    {
        $original = $this->createPendingLicense($this->customer);
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/license', [
                'license_number'   => 'ETH-REPLACEMENT',
                'full_name'        => $this->customer->name,
                'license_category' => 'automobile',
                'issue_date'       => now()->subYear()->toDateString(),
                'expiry_date'      => now()->addYears(4)->toDateString(),
                'front_document'   => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
                'back_document'    => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            ])
            ->assertStatus(201);

        // Old record must still be in the DB (soft-deleted or replaced status).
        $this->assertDatabaseHas('driver_licenses', [
            'id'     => $original->id,
            'status' => DriverLicense::STATUS_REPLACED,
        ]);

        // New pending record also exists.
        $this->assertDatabaseHas('driver_licenses', [
            'user_id' => $this->customer->id,
            'status'  => DriverLicense::STATUS_PENDING_REVIEW,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Booking eligibility
    // ─────────────────────────────────────────────────────────────────────────

    public function test_verified_valid_license_allows_booking(): void
    {
        $this->giveVerifiedLicense($this->customer);

        $result = $this->licenseService->checkEligibility($this->customer, $this->vehicle);

        $this->assertTrue($result['eligible']);
    }

    public function test_missing_license_blocks_booking(): void
    {
        $result = $this->licenseService->checkEligibility($this->customer, $this->vehicle);

        $this->assertFalse($result['eligible']);
        $this->assertEquals('LICENSE_NOT_SUBMITTED', $result['code']);
    }

    public function test_pending_license_blocks_booking(): void
    {
        $this->createPendingLicense($this->customer);

        $result = $this->licenseService->checkEligibility($this->customer, $this->vehicle);

        $this->assertFalse($result['eligible']);
        $this->assertEquals('LICENSE_PENDING', $result['code']);
    }

    public function test_rejected_license_blocks_booking(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $this->licenseService->reject($license, $this->admin, 'Unclear image.');

        $result = $this->licenseService->checkEligibility($this->customer, $this->vehicle);

        $this->assertFalse($result['eligible']);
        $this->assertEquals('LICENSE_REJECTED', $result['code']);
    }

    public function test_incompatible_license_category_blocks_booking(): void
    {
        // Customer has motorcycle license, vehicle requires automobile.
        DriverLicense::create([
            'user_id'           => $this->customer->id,
            'license_number'    => 'MOTO-001',
            'full_name'         => $this->customer->name,
            'license_category'  => DriverLicense::CATEGORY_MOTORCYCLE,
            'issue_date'        => now()->subYear()->toDateString(),
            'expiry_date'       => now()->addYears(4)->toDateString(),
            'status'            => DriverLicense::STATUS_VERIFIED,
            'submitted_at'      => now(),
        ]);

        $result = $this->licenseService->checkEligibility($this->customer, $this->vehicle);

        $this->assertFalse($result['eligible']);
        $this->assertEquals('LICENSE_CATEGORY_MISMATCH', $result['code']);
    }

    public function test_commercial_license_satisfies_automobile_requirement(): void
    {
        DriverLicense::create([
            'user_id'           => $this->customer->id,
            'license_number'    => 'COM-001',
            'full_name'         => $this->customer->name,
            'license_category'  => DriverLicense::CATEGORY_COMMERCIAL,
            'issue_date'        => now()->subYear()->toDateString(),
            'expiry_date'       => now()->addYears(4)->toDateString(),
            'status'            => DriverLicense::STATUS_VERIFIED,
            'submitted_at'      => now(),
        ]);

        $result = $this->licenseService->checkEligibility($this->customer, $this->vehicle);

        $this->assertTrue($result['eligible']);
    }

    public function test_booking_api_blocked_without_license(): void
    {
        // customer has NO license
        $token = $this->customer->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/bookings', [
                'vehicle_id'      => $this->vehicle->id,
                'pickup_location' => 'Airport',
                'return_location' => 'Hotel',
                'pickup_date'     => Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00'),
                'return_date'     => Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00'),
            ]);

        $response->assertStatus(422);
        // The license error is returned either as the main message or within errors.
        $body = json_encode($response->json());
        $this->assertStringContainsStringIgnoringCase('license', $body);
    }

    public function test_booking_api_blocked_with_pending_license(): void
    {
        $this->createPendingLicense($this->customer);
        $token = $this->customer->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/bookings', [
                'vehicle_id'      => $this->vehicle->id,
                'pickup_location' => 'Airport',
                'return_location' => 'Hotel',
                'pickup_date'     => Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00'),
                'return_date'     => Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00'),
            ]);

        $response->assertStatus(422);
    }

    public function test_booking_api_allowed_with_verified_license(): void
    {
        $this->giveVerifiedLicense($this->customer);
        $token = $this->customer->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/bookings', [
                'vehicle_id'      => $this->vehicle->id,
                'pickup_location' => 'Airport',
                'return_location' => 'Hotel',
                'pickup_date'     => Carbon::tomorrow()->addDays(1)->format('Y-m-d 10:00:00'),
                'return_date'     => Carbon::tomorrow()->addDays(3)->format('Y-m-d 10:00:00'),
            ]);

        $response->assertStatus(201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Authorization
    // ─────────────────────────────────────────────────────────────────────────

    public function test_unauthorized_user_cannot_approve_license(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/licenses/{$license->id}/approve")
            ->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_reject_license(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/licenses/{$license->id}/reject", ['reason' => 'test'])
            ->assertStatus(403);
    }

    public function test_customer_cannot_modify_another_customers_license(): void
    {
        $this->createPendingLicense($this->otherCustomer);
        $token = $this->customer->createToken('t')->plainTextToken;

        // Customer endpoint returns own license (null, because $customer has none).
        // Direct attempt to update other customer's license via service.
        $otherLicense = $this->licenseService->getActiveLicense($this->otherCustomer);

        $this->expectException(\InvalidArgumentException::class);
        $this->licenseService->updateDocuments($otherLicense, $this->customer);
    }

    public function test_admin_can_list_all_licenses(): void
    {
        $this->createPendingLicense($this->customer);
        $this->createPendingLicense($this->otherCustomer);

        $token = $this->admin->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/licenses');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_staff_can_review_licenses(): void
    {
        $license = $this->createPendingLicense($this->customer);
        $token = $this->staff->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/licenses/{$license->id}/approve")
            ->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Security — private document access
    // ─────────────────────────────────────────────────────────────────────────

    public function test_owner_can_access_own_document(): void
    {
        $license = $this->createLicenseWithDocs($this->customer);
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get("/api/licenses/{$license->id}/document/front")
            ->assertOk();
    }

    public function test_other_customer_cannot_access_document(): void
    {
        $license = $this->createLicenseWithDocs($this->customer);
        $token = $this->otherCustomer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get("/api/licenses/{$license->id}/document/front")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_document(): void
    {
        $license = $this->createLicenseWithDocs($this->customer);

        $this->get("/api/licenses/{$license->id}/document/front")
            ->assertStatus(401);
    }

    public function test_admin_can_access_any_document(): void
    {
        $license = $this->createLicenseWithDocs($this->customer);
        $token = $this->admin->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get("/api/licenses/{$license->id}/document/front")
            ->assertOk();
    }

    public function test_license_number_is_masked_in_list_response(): void
    {
        $this->createPendingLicense($this->customer);
        $token = $this->admin->createToken('t')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/licenses');

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // masked version should not equal the raw number
        $masked = $data[0]['license_number_masked'] ?? '';
        $this->assertStringContainsString('•', $masked);
    }

    public function test_eligibility_endpoint_returns_correct_result(): void
    {
        $this->giveVerifiedLicense($this->customer);
        $token = $this->customer->createToken('t')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/license/eligibility?vehicle_id=' . $this->vehicle->id)
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.code', 'OK');
    }

    public function test_license_submission_notifies_customer_admin_and_branch_manager(): void
    {
        Notification::fake();

        $this->licenseService->submit([
            'license_number'   => 'ETH-99887766',
            'full_name'        => $this->customer->name,
            'license_category' => 'automobile',
            'issue_date'       => now()->subYear()->toDateString(),
            'expiry_date'      => now()->addYears(4)->toDateString(),
        ], $this->customer);

        Notification::assertSentTo($this->customer, LicenseSubmitted::class);
        Notification::assertSentTo($this->admin, AdminLicenseSubmitted::class);
        Notification::assertSentTo($this->manager, AdminLicenseSubmitted::class);
        Notification::assertNotSentTo($this->staff, AdminLicenseSubmitted::class);
    }

    public function test_license_approval_notifies_customer(): void
    {
        Notification::fake();

        $license = $this->createPendingLicense($this->customer);
        $this->licenseService->approve($license, $this->admin);

        Notification::assertSentTo($this->customer, LicenseApproved::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function createPendingLicense(User $customer): DriverLicense
    {
        return DriverLicense::create([
            'user_id'           => $customer->id,
            'license_number'    => 'TEST-' . rand(10000, 99999),
            'full_name'         => $customer->name,
            'license_category'  => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date'        => now()->subYear()->toDateString(),
            'expiry_date'       => now()->addYears(4)->toDateString(),
            'issuing_authority' => 'Test Authority',
            'status'            => DriverLicense::STATUS_PENDING_REVIEW,
            'submitted_at'      => now(),
        ]);
    }

    private function createLicenseWithDocs(User $customer): DriverLicense
    {
        Storage::disk('local')->put("licenses/{$customer->id}/front_test.jpg", 'fake-image-data');
        Storage::disk('local')->put("licenses/{$customer->id}/back_test.jpg", 'fake-image-data');

        return DriverLicense::create([
            'user_id'              => $customer->id,
            'license_number'       => 'DOCS-' . rand(10000, 99999),
            'full_name'            => $customer->name,
            'license_category'     => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date'           => now()->subYear()->toDateString(),
            'expiry_date'          => now()->addYears(4)->toDateString(),
            'status'               => DriverLicense::STATUS_PENDING_REVIEW,
            'submitted_at'         => now(),
            'front_document_path'  => "licenses/{$customer->id}/front_test.jpg",
            'back_document_path'   => "licenses/{$customer->id}/back_test.jpg",
        ]);
    }
}
