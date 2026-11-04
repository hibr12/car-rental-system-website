<?php

namespace Tests\Feature\License;

use App\Models\DriverLicense;
use App\Models\User;
use App\Notifications\LicenseApproved;
use App\Services\AuditLogService;
use App\Services\DriverLicenseService;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DriverLicenseReviewFailureTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private DriverLicense $license;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->customer = User::factory()->customer()->create();
        $this->admin = User::factory()->admin()->create();
        $this->license = $this->createPendingLicense();
    }

    public function test_audit_failure_cannot_undo_approval_and_review_runs_without_a_transaction(): void
    {
        $baselineTransactionLevel = DB::transactionLevel();
        $auditTransactionLevel = null;
        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')
            ->once()
            ->andReturnUsing(function () use (&$auditTransactionLevel): void {
                $auditTransactionLevel = DB::transactionLevel();

                throw new RuntimeException('Synthetic approval audit failure.');
            });

        $service = new DriverLicenseService(
            $audit,
            app(NotificationRecipientService::class),
        );

        $approved = $service->approve($this->license, $this->admin);

        $this->assertSame($baselineTransactionLevel, $auditTransactionLevel);
        $this->assertSame(DriverLicense::STATUS_VERIFIED, $approved->status);
        $this->assertSame($this->admin->id, $approved->verified_by);
        $this->assertNotNull($approved->verified_at);
        $this->assertDatabaseHas('driver_licenses', [
            'id' => $this->license->id,
            'status' => DriverLicense::STATUS_VERIFIED,
            'verified_by' => $this->admin->id,
        ]);
    }

    public function test_notification_failure_still_returns_success_and_customer_sees_verified_status(): void
    {
        $dispatcher = new class implements NotificationDispatcher
        {
            public function send($notifiables, $notification): void
            {
                throw new RuntimeException('Synthetic approval notification failure.');
            }

            public function sendNow($notifiables, $notification, ?array $channels = null): void
            {
                throw new RuntimeException('Synthetic approval notification failure.');
            }
        };
        $this->app->instance(NotificationDispatcher::class, $dispatcher);

        $adminToken = $this->admin->createToken('approval-test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/admin/licenses/{$this->license->id}/approve")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', DriverLicense::STATUS_VERIFIED)
            ->assertJsonPath('data.reviewer.id', $this->admin->id);

        $customerToken = $this->customer->createToken('approval-customer-test')->plainTextToken;
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$customerToken)
            ->getJson('/api/customer/license')
            ->assertOk()
            ->assertJsonPath('data.id', $this->license->id)
            ->assertJsonPath('data.status', DriverLicense::STATUS_VERIFIED);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'license_approved',
            'entity_type' => 'driver_license',
            'entity_id' => $this->license->id,
        ]);
    }

    public function test_approval_endpoint_sends_exactly_one_customer_notification(): void
    {
        $adminToken = $this->admin->createToken('approval-notification-test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/admin/licenses/{$this->license->id}/approve")
            ->assertOk();

        Notification::assertSentToTimes($this->customer, LicenseApproved::class, 1);
    }

    public function test_rejection_uses_the_same_transactionless_review_path(): void
    {
        $baselineTransactionLevel = DB::transactionLevel();
        $auditTransactionLevel = null;
        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')
            ->once()
            ->andReturnUsing(function () use (&$auditTransactionLevel): void {
                $auditTransactionLevel = DB::transactionLevel();

                throw new RuntimeException('Synthetic rejection audit failure.');
            });

        $service = new DriverLicenseService(
            $audit,
            app(NotificationRecipientService::class),
        );

        $rejected = $service->reject($this->license, $this->admin, 'The document is unreadable.');

        $this->assertSame($baselineTransactionLevel, $auditTransactionLevel);
        $this->assertSame(DriverLicense::STATUS_REJECTED, $rejected->status);
        $this->assertSame($this->admin->id, $rejected->verified_by);
        $this->assertDatabaseHas('driver_licenses', [
            'id' => $this->license->id,
            'status' => DriverLicense::STATUS_REJECTED,
            'verified_by' => $this->admin->id,
            'rejection_reason' => 'The document is unreadable.',
        ]);
    }

    public function test_unexpected_approval_error_returns_a_safe_traceable_response(): void
    {
        $service = Mockery::mock(DriverLicenseService::class);
        $service->shouldReceive('approve')
            ->once()
            ->andThrow(new RuntimeException('Sensitive database failure details.'));
        $this->app->instance(DriverLicenseService::class, $service);

        $adminToken = $this->admin->createToken('approval-error-test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson("/api/admin/licenses/{$this->license->id}/approve");

        $response
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'LICENSE_REVIEW_FAILED')
            ->assertJsonStructure(['error_id'])
            ->assertJsonMissing(['Sensitive database failure details.']);

        $this->assertDatabaseHas('driver_licenses', [
            'id' => $this->license->id,
            'status' => DriverLicense::STATUS_PENDING_REVIEW,
        ]);
    }

    private function createPendingLicense(): DriverLicense
    {
        return DriverLicense::create([
            'user_id' => $this->customer->id,
            'document_type' => DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE,
            'document_number' => 'ETH-REVIEW-FAILURE-1',
            'full_name' => $this->customer->name,
            'license_category' => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYears(4)->toDateString(),
            'status' => DriverLicense::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);
    }
}
