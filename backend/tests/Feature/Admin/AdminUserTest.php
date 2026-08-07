<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private string $adminToken;
    private string $customerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();

        $this->adminToken = $this->admin->createToken('auth-token')->plainTextToken;
        $this->customerToken = $this->customer->createToken('auth-token')->plainTextToken;
    }

    public function test_admin_can_list_users(): void
    {
        User::factory()->count(5)->customer()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta',
            ]);
    }

    public function test_customer_cannot_list_users(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->customerToken)
            ->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_user_details(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/users/' . $this->customer->id);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['id' => $this->customer->id],
            ]);
    }

    public function test_customer_cannot_view_other_user_details(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->customerToken)
            ->getJson('/api/admin/users/' . $this->admin->id);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_user(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/admin/users/' . $this->customer->id, [
                'name' => 'Updated Customer',
                'email' => 'updated@example.com',
                'phone' => '9876543210',
                'role' => 'staff',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.role', 'staff');

        $this->customer->refresh();
        $this->assertSame('Updated Customer', $this->customer->name);
        $this->assertSame('staff', $this->customer->role);
    }

    public function test_admin_can_update_user_password(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/admin/users/' . $this->customer->id, [
                'password' => 'NewStrongPass123',
            ]);

        $response->assertOk();

        $this->customer->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewStrongPass123', $this->customer->password));
    }

    public function test_customer_cannot_update_user(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->customerToken)
            ->putJson('/api/admin/users/' . $this->admin->id, [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin_users(): void
    {
        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(401);
    }

    public function test_admin_user_list_is_paginated(): void
    {
        User::factory()->count(20)->customer()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/users?page=1');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }
}
