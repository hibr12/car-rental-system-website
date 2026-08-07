<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard_stats(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['summary', 'monthly_revenue', 'booking_statuses', 'maintenance_costs', 'revenue_summary'],
            ]);
    }

    public function test_admin_can_manage_maintenance_records(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/maintenance', [
                'vehicle_id' => $vehicle->id,
                'title' => 'Oil change',
                'description' => 'Quarterly service',
                'maintenance_type' => 'service',
                'cost' => 120,
                'start_date' => now()->toDateTimeString(),
                'status' => 'scheduled',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('maintenances', ['title' => 'Oil change']);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'maintenance']);
    }

    public function test_admin_can_manage_contact_messages(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;
        $message = ContactMessage::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/contact-messages/' . $message->id, ['status' => 'replied']);

        $response->assertOk();
        $this->assertDatabaseHas('contact_messages', ['id' => $message->id, 'status' => 'replied']);
    }

    public function test_admin_can_update_user_role_and_password(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;
        $user = User::factory()->customer()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/users/' . $user->id, [
                'name' => 'Updated User',
                'email' => 'updated.user@example.com',
                'phone' => '1234567890',
                'role' => 'staff',
                'password' => 'StrongPassword123',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.role', 'staff');

        $user->refresh();
        $this->assertSame('Updated User', $user->name);
        $this->assertSame('updated.user@example.com', $user->email);
        $this->assertSame('1234567890', $user->phone);
        $this->assertSame('staff', $user->role);
        $this->assertTrue(Hash::check('StrongPassword123', $user->password));
    }

    public function test_admin_can_complete_maintenance_and_release_vehicle(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;
        $category = Category::factory()->create();
        $vehicle = Vehicle::factory()->available()->create(['category_id' => $category->id]);
        $maintenance = Maintenance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'created_by' => $admin->id,
            'status' => 'scheduled',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/maintenance/' . $maintenance->id, [
                'status' => 'completed',
                'cost' => 200,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('maintenances', ['id' => $maintenance->id, 'status' => 'completed']);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'available']);
    }

    public function test_admin_can_mark_contact_message_as_replied_with_timestamp(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;
        $message = ContactMessage::factory()->create(['status' => 'pending']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/contact-messages/' . $message->id, ['status' => 'replied']);

        $response->assertOk();
        $message->refresh();
        $this->assertSame('replied', $message->status);
        $this->assertNotNull($message->replied_at);
    }

    public function test_dashboard_returns_structured_reporting_data(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'summary',
                    'monthly_revenue',
                    'booking_statuses',
                    'maintenance_costs',
                    'revenue_summary',
                    'recent_bookings',
                    'recent_users',
                    'popular_vehicles',
                ],
            ]);
    }
}
