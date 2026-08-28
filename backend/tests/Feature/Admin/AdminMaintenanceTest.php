<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
