<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_route(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $response->assertOk();
    }

    public function test_staff_can_access_admin_staff_route(): void
    {
        $staff = User::factory()->staff()->create();
        $token = $staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bookings');

        $response->assertOk();
    }

    public function test_customer_cannot_access_admin_route(): void
    {
        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized. Insufficient permissions.',
            ]);
    }

    public function test_customer_cannot_access_maintenance_route(): void
    {
        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/maintenance');

        $response->assertStatus(403);
    }

    public function test_fleet_manager_can_access_maintenance_route(): void
    {
        $fleetManager = User::factory()->fleetManager()->create();
        $token = $fleetManager->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/maintenance');

        $response->assertOk();
    }

    public function test_customer_cannot_access_contact_messages_route(): void
    {
        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/contact-messages');

        $response->assertStatus(403);
    }

    public function test_customer_cannot_access_admin_users_route(): void
    {
        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_gets_401_not_403(): void
    {
        $response = $this->getJson('/api/admin/dashboard');
        $response->assertStatus(401);
    }

    public function test_staff_can_access_admin_bookings(): void
    {
        $staff = User::factory()->staff()->create();
        $token = $staff->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bookings');

        $response->assertOk();
    }

    public function test_fleet_manager_cannot_access_admin_bookings(): void
    {
        $fleetManager = User::factory()->fleetManager()->create();
        $token = $fleetManager->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bookings');

        $response->assertStatus(403);
    }
}
