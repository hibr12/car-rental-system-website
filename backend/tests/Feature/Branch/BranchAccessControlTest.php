<?php

namespace Tests\Feature\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Maintenance;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchCmc;
    private Branch $branchBole;
    private User $admin;
    private User $fleetManager;
    private User $cmcManager;
    private User $boleManager;
    private User $cmcStaff;
    private User $customer;
    private Category $category;
    private Vehicle $cmcVehicle;
    private Vehicle $boleVehicle;
    private Booking $cmcBooking;
    private Booking $boleBooking;
    private Payment $cmcPayment;
    private Payment $bolePayment;
    private Maintenance $cmcMaintenance;
    private Maintenance $boleMaintenance;
    private User $cmcStaffMember;
    private User $boleStaffMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchCmc = Branch::factory()->create(['code' => 'CMC', 'name' => 'CMC Branch']);
        $this->branchBole = Branch::factory()->create(['code' => 'BOLE', 'name' => 'Bole Branch']);
        $this->category = Category::factory()->create();

        $this->admin = User::factory()->admin()->create();
        $this->fleetManager = User::factory()->fleetManager()->create();
        $this->cmcManager = User::factory()->branchManager()->create(['branch_id' => $this->branchCmc->id]);
        $this->boleManager = User::factory()->branchManager()->create(['branch_id' => $this->branchBole->id]);
        $this->cmcStaff = User::factory()->staff()->create(['branch_id' => $this->branchCmc->id]);
        $this->customer = User::factory()->customer()->create();

        $this->cmcVehicle = Vehicle::factory()->create([
            'branch_id' => $this->branchCmc->id,
            'category_id' => $this->category->id,
            'brand' => 'Toyota',
            'model' => 'Camry',
        ]);
        $this->boleVehicle = Vehicle::factory()->create([
            'branch_id' => $this->branchBole->id,
            'category_id' => $this->category->id,
            'brand' => 'Honda',
            'model' => 'Fit',
        ]);

        $this->cmcBooking = Booking::factory()->create([
            'branch_id' => $this->branchCmc->id,
            'vehicle_id' => $this->cmcVehicle->id,
        ]);
        $this->boleBooking = Booking::factory()->create([
            'branch_id' => $this->branchBole->id,
            'vehicle_id' => $this->boleVehicle->id,
        ]);

        $this->cmcPayment = Payment::create([
            'booking_id' => $this->cmcBooking->id,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branchCmc->id,
            'amount' => 500,
            'expected_amount' => 500,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_CASH,
            'status' => Payment::STATUS_CASH_PENDING,
            'attempt_number' => 1,
        ]);
        $this->bolePayment = Payment::create([
            'booking_id' => $this->boleBooking->id,
            'user_id' => $this->customer->id,
            'branch_id' => $this->branchBole->id,
            'amount' => 600,
            'expected_amount' => 600,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_CASH,
            'status' => Payment::STATUS_CASH_PENDING,
            'attempt_number' => 1,
        ]);

        $this->cmcMaintenance = Maintenance::factory()->create([
            'vehicle_id' => $this->cmcVehicle->id,
            'branch_id' => $this->branchCmc->id,
            'created_by' => $this->cmcManager->id,
        ]);
        $this->boleMaintenance = Maintenance::factory()->create([
            'vehicle_id' => $this->boleVehicle->id,
            'branch_id' => $this->branchBole->id,
            'created_by' => $this->boleManager->id,
        ]);

        $this->cmcStaffMember = User::factory()->staff()->create([
            'branch_id' => $this->branchCmc->id,
            'email' => 'cmc.staff@apexrentals.com',
        ]);
        $this->boleStaffMember = User::factory()->staff()->create([
            'branch_id' => $this->branchBole->id,
            'email' => 'bole.staff@apexrentals.com',
        ]);
    }

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function auth(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->token($user));
    }

    // ─── Vehicle Access ─────────────────────────────────────────────

    public function test_admin_can_view_any_vehicle(): void
    {
        $this->auth($this->admin)
            ->getJson("/api/vehicles/{$this->boleVehicle->id}")
            ->assertOk();
    }

    public function test_fleet_manager_can_view_any_vehicle(): void
    {
        $this->auth($this->fleetManager)
            ->getJson("/api/vehicles/{$this->cmcVehicle->id}")
            ->assertOk();

        $this->auth($this->fleetManager)
            ->getJson("/api/vehicles/{$this->boleVehicle->id}")
            ->assertOk();
    }

    public function test_cmc_manager_can_view_own_vehicle(): void
    {
        $this->auth($this->cmcManager)
            ->getJson("/api/vehicles/{$this->cmcVehicle->id}")
            ->assertOk();
    }

    public function test_cmc_manager_cannot_view_bole_vehicle_by_id(): void
    {
        $this->auth($this->cmcManager)
            ->getJson("/api/vehicles/{$this->boleVehicle->id}")
            ->assertForbidden();
    }

    public function test_cmc_manager_vehicle_list_scoped_to_own_branch(): void
    {
        $this->auth($this->cmcManager)
            ->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_cmc_manager_cannot_filter_vehicles_by_bole_branch(): void
    {
        $this->auth($this->cmcManager)
            ->getJson("/api/vehicles?branch_id={$this->branchBole->id}")
            ->assertForbidden();
    }

    public function test_cmc_staff_vehicle_list_scoped_to_own_branch(): void
    {
        $this->auth($this->cmcStaff)
            ->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_cmc_staff_cannot_view_bole_vehicle(): void
    {
        $this->auth($this->cmcStaff)
            ->getJson("/api/vehicles/{$this->boleVehicle->id}")
            ->assertForbidden();
    }

    public function test_fleet_manager_sees_all_vehicles(): void
    {
        $this->auth($this->fleetManager)
            ->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_customer_can_browse_vehicles_across_branches(): void
    {
        $this->auth($this->customer)
            ->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_cmc_manager_create_vehicle_forces_own_branch(): void
    {
        $this->auth($this->cmcManager)
            ->postJson('/api/vehicles', [
                'category_id' => $this->category->id,
                'branch_id' => $this->branchBole->id,
                'brand' => 'Nissan',
                'model' => 'Altima',
                'year' => 2024,
                'registration_number' => 'CMC-NEW-001',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seats' => 5,
                'rental_price_per_day' => 150,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('vehicles', [
            'registration_number' => 'CMC-NEW-001',
            'branch_id' => $this->branchCmc->id,
        ]);
    }

    public function test_cmc_manager_cannot_update_bole_vehicle(): void
    {
        $this->auth($this->cmcManager)
            ->putJson("/api/vehicles/{$this->boleVehicle->id}", [
                'brand' => 'Hacked',
            ])
            ->assertForbidden();
    }

    // ─── Booking Access ─────────────────────────────────────────────

    public function test_cmc_manager_sees_only_own_branch_bookings(): void
    {
        $this->auth($this->cmcManager)
            ->getJson('/api/admin/bookings')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_cmc_manager_cannot_access_bole_booking(): void
    {
        $this->auth($this->cmcManager)
            ->putJson("/api/admin/bookings/{$this->boleBooking->id}/confirm")
            ->assertForbidden();
    }

    public function test_admin_sees_all_bookings(): void
    {
        $this->auth($this->admin)
            ->getJson('/api/admin/bookings')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    // ─── Payment Access ─────────────────────────────────────────────

    public function test_cmc_manager_cannot_verify_bole_payment(): void
    {
        $this->auth($this->cmcManager)
            ->postJson("/api/payments/{$this->bolePayment->id}/verify")
            ->assertForbidden();
    }

    public function test_cmc_manager_payment_history_scoped_to_branch(): void
    {
        $this->auth($this->cmcManager)
            ->getJson('/api/admin/payment-history')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    // ─── Staff Access ───────────────────────────────────────────────

    public function test_cmc_manager_sees_only_own_branch_staff(): void
    {
        $this->auth($this->cmcManager)
            ->getJson('/api/staff')
            ->assertOk();

        $emails = collect(json_decode($this->auth($this->cmcManager)->getJson('/api/staff')->getContent(), true)['data'])
            ->pluck('email');

        $this->assertTrue($emails->contains('cmc.staff@apexrentals.com'));
        $this->assertFalse($emails->contains('bole.staff@apexrentals.com'));
    }

    public function test_cmc_manager_cannot_update_bole_staff(): void
    {
        $this->auth($this->cmcManager)
            ->putJson("/api/staff/{$this->boleStaffMember->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_cmc_manager_create_staff_forces_own_branch(): void
    {
        $this->auth($this->cmcManager)
            ->postJson('/api/staff', [
                'name' => 'New CMC Staff',
                'email' => 'new.cmc.staff@apexrentals.com',
                'password' => 'password123',
                'role' => 'staff',
                'branch_id' => $this->branchBole->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'new.cmc.staff@apexrentals.com',
            'branch_id' => $this->branchCmc->id,
        ]);
    }

    public function test_cmc_manager_cannot_create_fleet_manager(): void
    {
        $this->auth($this->cmcManager)
            ->postJson('/api/staff', [
                'name' => 'Fake Fleet',
                'email' => 'fake.fleet@apexrentals.com',
                'password' => 'password123',
                'role' => 'fleet_manager',
                'branch_id' => $this->branchCmc->id,
            ])
            ->assertStatus(422);
    }

    // ─── Maintenance Access ───────────────────────────────────────────

    public function test_cmc_manager_sees_only_own_branch_maintenance(): void
    {
        $this->auth($this->cmcManager)
            ->getJson('/api/maintenance')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_cmc_manager_cannot_view_bole_maintenance(): void
    {
        $this->auth($this->cmcManager)
            ->getJson("/api/maintenance/{$this->boleMaintenance->id}")
            ->assertForbidden();
    }

    public function test_fleet_manager_sees_all_maintenance(): void
    {
        $this->auth($this->fleetManager)
            ->getJson('/api/maintenance')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    // ─── Transfer Destinations ────────────────────────────────────────

    public function test_cmc_manager_can_list_transfer_destinations(): void
    {
        $this->auth($this->cmcManager)
            ->getJson('/api/branches/transfer-destinations')
            ->assertOk();

        $ids = collect(json_decode(
            $this->auth($this->cmcManager)->getJson('/api/branches/transfer-destinations')->getContent(),
            true
        )['data'])->pluck('id');

        $this->assertTrue($ids->contains($this->branchBole->id));
        $this->assertFalse($ids->contains($this->branchCmc->id));
    }

    public function test_customer_cannot_access_transfer_destinations(): void
    {
        $this->auth($this->customer)
            ->getJson('/api/branches/transfer-destinations')
            ->assertForbidden();
    }
}
