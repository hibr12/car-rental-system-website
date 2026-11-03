<?php

namespace Tests\Feature\License;

use App\Exceptions\LicenseDocumentStorageException;
use App\Models\DriverLicense;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\DriverLicenseService;
use App\Services\NotificationRecipientService;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DriverLicenseSubmissionFailureTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['services.cloudinary.enabled' => false]);

        $this->customer = User::factory()->customer()->create();
    }

    public function test_second_document_failure_cleans_the_first_upload_and_rolls_back(): void
    {
        $front = UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg');
        $backSource = UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg');
        $back = new ThrowingLicenseUploadedFile(
            $backSource->getPathname(),
            $backSource->getClientOriginalName(),
            $backSource->getClientMimeType(),
            null,
            true,
        );

        try {
            app(DriverLicenseService::class)->submit(
                $this->validLicenseData(),
                $this->customer,
                $front,
                $back,
            );

            $this->fail('The storage failure should have been propagated as a domain exception.');
        } catch (LicenseDocumentStorageException $e) {
            $this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
        }

        $this->assertDatabaseCount('driver_licenses', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('licenses/'.$this->customer->id));
    }

    public function test_audit_failure_does_not_fail_committed_submission_or_delete_uploads(): void
    {
        $previous = DriverLicense::create([
            'user_id' => $this->customer->id,
            'document_type' => DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE,
            'document_number' => 'ETH-PREVIOUS-1',
            'full_name' => $this->customer->name,
            'license_category' => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date' => now()->subYears(2)->toDateString(),
            'expiry_date' => now()->addYears(2)->toDateString(),
            'status' => DriverLicense::STATUS_REJECTED,
            'submitted_at' => now()->subDay(),
        ]);

        $baselineTransactionLevel = DB::transactionLevel();
        $auditTransactionLevel = null;
        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')
            ->once()
            ->andReturnUsing(function () use (&$auditTransactionLevel): void {
                $auditTransactionLevel = DB::transactionLevel();

                throw new RuntimeException('Synthetic audit write failure.');
            });

        $service = new DriverLicenseService(
            $audit,
            app(NotificationRecipientService::class),
        );

        $license = $service->submit(
            $this->validLicenseData(),
            $this->customer,
            UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
            UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
        );

        $previous->refresh();

        $this->assertSame($baselineTransactionLevel, $auditTransactionLevel);
        $this->assertSame(DriverLicense::STATUS_REPLACED, $previous->status);
        $this->assertSame($license->id, $previous->replaced_by);
        $this->assertDatabaseCount('driver_licenses', 2);
        Storage::disk('local')->assertExists($license->front_document_path);
        Storage::disk('local')->assertExists($license->back_document_path);
    }

    public function test_failed_document_replacement_keeps_existing_files_and_cleans_staged_uploads(): void
    {
        $oldFront = 'licenses/'.$this->customer->id.'/old-front.jpg';
        $oldBack = 'licenses/'.$this->customer->id.'/old-back.jpg';
        Storage::disk('local')->put($oldFront, 'old front');
        Storage::disk('local')->put($oldBack, 'old back');

        $license = DriverLicense::create([
            'user_id' => $this->customer->id,
            'document_type' => DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE,
            'document_number' => 'ETH-REPLACE-1',
            'full_name' => $this->customer->name,
            'license_category' => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYears(4)->toDateString(),
            'front_document_path' => $oldFront,
            'back_document_path' => $oldBack,
            'status' => DriverLicense::STATUS_PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        $backSource = UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg');
        $failingBack = new ThrowingLicenseUploadedFile(
            $backSource->getPathname(),
            $backSource->getClientOriginalName(),
            $backSource->getClientMimeType(),
            null,
            true,
        );

        try {
            app(DriverLicenseService::class)->updateDocuments(
                $license,
                $this->customer,
                UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
                $failingBack,
            );

            $this->fail('The replacement storage failure should have been propagated.');
        } catch (LicenseDocumentStorageException) {
            // The existing document paths and files must remain usable.
        }

        $license->refresh();

        $this->assertSame($oldFront, $license->front_document_path);
        $this->assertSame($oldBack, $license->back_document_path);
        Storage::disk('local')->assertExists($oldFront);
        Storage::disk('local')->assertExists($oldBack);

        $remainingFiles = Storage::disk('local')->allFiles('licenses/'.$this->customer->id);
        sort($remainingFiles);

        $this->assertSame([$oldBack, $oldFront], $remainingFiles);
    }

    public function test_notification_failure_happens_after_commit_and_does_not_fail_submission(): void
    {
        $baselineTransactionLevel = DB::transactionLevel();
        $transactionLevel = null;

        $dispatcher = new class(function () use (&$transactionLevel): void {
            $transactionLevel = DB::transactionLevel();

            throw new RuntimeException('Synthetic notification failure.');
        }) implements NotificationDispatcher
        {

            public function __construct(private \Closure $onSend) {}

            public function send($notifiables, $notification): void
            {
                ($this->onSend)();
            }

            public function sendNow($notifiables, $notification, ?array $channels = null): void
            {
                ($this->onSend)();
            }
        };

        $this->app->instance(NotificationDispatcher::class, $dispatcher);

        $license = app(DriverLicenseService::class)->submit(
            $this->validLicenseData(),
            $this->customer,
            UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
            UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
        );

        $this->assertSame($baselineTransactionLevel, $transactionLevel);
        $this->assertTrue($license->exists);
        $this->assertDatabaseHas('driver_licenses', [
            'id' => $license->id,
            'user_id' => $this->customer->id,
            'status' => DriverLicense::STATUS_PENDING_REVIEW,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function validLicenseData(): array
    {
        return [
            'document_type' => DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE,
            'document_number' => 'ETH-FAILURE-1234',
            'full_name' => $this->customer->name,
            'license_category' => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYears(4)->toDateString(),
        ];
    }
}

final class ThrowingLicenseUploadedFile extends UploadedFile
{
    public function storeAs($path, $name = null, $options = [])
    {
        throw new RuntimeException('Synthetic second document storage failure.');
    }
}
