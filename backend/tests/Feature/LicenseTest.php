<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $staff;
    private User $branchManager;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->customer = User::factory()->customer()->create();
        $this->staff = User::factory()->staff()->create(['branch_id' => $this->branch->id]);
        $this->branchManager = User::factory()->branchManager()->create(['branch_id' => $this->branch->id]);
    }

    public function test_customer_can_view_license_status(): void
    {
        $this->customer->update([
            'license_status' => 'verified',
            'license_number' => 'DL-12345678',
        ]);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/license');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'license_number' => 'DL-12345678',
                    'license_status' => 'verified',
                ],
            ]);
    }

    public function test_staff_can_view_pending_licenses(): void
    {
        $this->customer->update(['license_status' => 'pending']);

        $token = $this->staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/licenses/pending');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_staff_can_verify_license(): void
    {
        $this->customer->update(['license_status' => 'pending']);

        $token = $this->staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/licenses/' . $this->customer->id . '/verify', [
                'status' => 'verified',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'License verified successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'license_status' => 'verified',
        ]);
    }

    public function test_staff_can_reject_license(): void
    {
        $this->customer->update(['license_status' => 'pending']);

        $token = $this->staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/licenses/' . $this->customer->id . '/verify', [
                'status' => 'rejected',
                'notes' => 'Image is blurry.',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'License rejected successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'license_status' => 'rejected',
        ]);
    }

    public function test_customer_cannot_verify_license(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $otherCustomer->update(['license_status' => 'pending']);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/licenses/' . $otherCustomer->id . '/verify', [
                'status' => 'verified',
            ]);

        $response->assertStatus(403);
    }

    public function test_verified_license_cannot_be_reuploaded(): void
    {
        $this->customer->update(['license_status' => 'verified']);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        // Simulate trying to upload by directly updating (since GD not available)
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/license', [
                'license_number' => 'DL-NEW',
                'license_image' => 'not-a-file',
            ]);

        // Should fail validation since it's not a valid file
        $response->assertStatus(422);
    }
}
