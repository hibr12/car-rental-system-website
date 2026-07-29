<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService();
    }

    public function test_dashboard_returns_all_required_sections(): void
    {
        $stats = $this->service->getDashboardStats();

        $this->assertArrayHasKey('summary', $stats);
        $this->assertArrayHasKey('monthly_revenue', $stats);
        $this->assertArrayHasKey('booking_statuses', $stats);
        $this->assertArrayHasKey('maintenance_costs', $stats);
        $this->assertArrayHasKey('revenue_summary', $stats);
        $this->assertArrayHasKey('recent_bookings', $stats);
        $this->assertArrayHasKey('recent_users', $stats);
        $this->assertArrayHasKey('popular_vehicles', $stats);
        $this->assertArrayHasKey('report_sections', $stats);
    }

    public function test_dashboard_summary_counts(): void
    {
        $users = User::factory()->count(3)->customer()->create();
        $category = Category::factory()->create();
        Vehicle::factory()->available()->count(2)->create(['category_id' => $category->id]);
        Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'rented']);
        Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'maintenance']);
        $bookedVehicle1 = Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'reserved']);
        $bookedVehicle2 = Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'rented']);
        $bookedVehicle3 = Vehicle::factory()->create(['category_id' => $category->id, 'status' => 'available']);

        Booking::factory()->pending()->create([
            'vehicle_id' => $bookedVehicle1->id,
            'user_id' => $users[0]->id,
        ]);
        Booking::factory()->create([
            'status' => 'active',
            'vehicle_id' => $bookedVehicle2->id,
            'user_id' => $users[1]->id,
        ]);
        Booking::factory()->completed()->create([
            'vehicle_id' => $bookedVehicle3->id,
            'user_id' => $users[2]->id,
        ]);
        ContactMessage::factory()->create(['status' => 'pending']);
        Maintenance::factory()->create([
            'created_by' => $users[0]->id,
            'vehicle_id' => $bookedVehicle3->id,
        ]);

        $stats = $this->service->getDashboardStats();
        $summary = $stats['summary'];

        $this->assertEquals(3, $summary['total_customers']);
        $this->assertEquals(7, $summary['total_vehicles']);
        $this->assertEquals(3, $summary['available_vehicles']);
        $this->assertEquals(2, $summary['rented_vehicles']);
        $this->assertEquals(1, $summary['vehicles_under_maintenance']);
        $this->assertEquals(3, $summary['total_bookings']);
        $this->assertEquals(1, $summary['pending_bookings']);
        $this->assertEquals(1, $summary['active_rentals']);
        $this->assertEquals(1, $summary['completed_rentals']);
        $this->assertEquals(1, $summary['pending_messages']);
        $this->assertEquals(1, $summary['maintenance_count']);
    }

    public function test_revenue_summary(): void
    {
        $category = Category::factory()->create();
        $v1 = Vehicle::factory()->create(['category_id' => $category->id]);
        $v2 = Vehicle::factory()->create(['category_id' => $category->id]);
        $v3 = Vehicle::factory()->create(['category_id' => $category->id]);

        Booking::factory()->create(['vehicle_id' => $v1->id, 'total_price' => 100, 'payment_status' => 'paid']);
        Booking::factory()->create(['vehicle_id' => $v2->id, 'total_price' => 200, 'payment_status' => 'pending']);
        Booking::factory()->create(['vehicle_id' => $v3->id, 'total_price' => 150, 'payment_status' => 'unpaid']);

        $summary = $this->service->revenueSummary();

        $this->assertEquals(450, $summary['total']);
        $this->assertEquals(100, $summary['paid']);
        $this->assertEquals(200, $summary['pending']);
        $this->assertEquals(150, $summary['unpaid']);
    }

    public function test_booking_status_breakdown(): void
    {
        $category = Category::factory()->create();
        Booking::factory()->pending()->count(2)->create(['vehicle_id' => Vehicle::factory()->create(['category_id' => $category->id])->id]);
        Booking::factory()->confirmed()->count(3)->create(['vehicle_id' => Vehicle::factory()->create(['category_id' => $category->id])->id]);
        Booking::factory()->completed()->create(['vehicle_id' => Vehicle::factory()->create(['category_id' => $category->id])->id]);

        $breakdown = $this->service->bookingStatusBreakdown();

        $this->assertIsArray($breakdown);
        $this->assertCount(3, $breakdown);

        $statuses = collect($breakdown)->pluck('status')->toArray();
        $this->assertContains('pending', $statuses);
        $this->assertContains('confirmed', $statuses);
        $this->assertContains('completed', $statuses);
    }

    public function test_maintenance_cost_breakdown(): void
    {
        Maintenance::factory()->count(2)->create(['status' => 'completed', 'cost' => 100]);
        Maintenance::factory()->create(['status' => 'in_progress', 'cost' => 200]);

        $breakdown = $this->service->maintenanceCostBreakdown();

        $this->assertIsArray($breakdown);
        $this->assertCount(2, $breakdown);
    }

    public function test_popular_vehicles(): void
    {
        $category = Category::factory()->create();
        $v1 = Vehicle::factory()->create(['category_id' => $category->id]);
        $v2 = Vehicle::factory()->create(['category_id' => $category->id]);

        Booking::factory()->count(3)->create(['vehicle_id' => $v1->id]);
        Booking::factory()->count(1)->create(['vehicle_id' => $v2->id]);

        $stats = $this->service->getDashboardStats();

        $this->assertNotEmpty($stats['popular_vehicles']);
        $this->assertEquals($v1->id, $stats['popular_vehicles'][0]->vehicle_id);
    }

    public function test_recent_bookings_limited_to_5(): void
    {
        $category = Category::factory()->create();
        Booking::factory()->count(8)->create(['vehicle_id' => Vehicle::factory()->create(['category_id' => $category->id])->id]);

        $stats = $this->service->getDashboardStats();

        $this->assertCount(5, $stats['recent_bookings']);
    }

    public function test_recent_users_limited_to_5(): void
    {
        User::factory()->count(8)->customer()->create();

        $stats = $this->service->getDashboardStats();

        $this->assertCount(5, $stats['recent_users']);
    }

    public function test_report_sections(): void
    {
        $stats = $this->service->getDashboardStats();

        $this->assertContains('overview', $stats['report_sections']);
        $this->assertContains('revenue', $stats['report_sections']);
        $this->assertContains('bookings', $stats['report_sections']);
        $this->assertContains('maintenance', $stats['report_sections']);
        $this->assertContains('activity', $stats['report_sections']);
    }
}
