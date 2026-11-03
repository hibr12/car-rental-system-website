<?php

namespace Tests\Feature\License;

use App\Events\LicenseSubmitted;
use App\Models\DriverLicense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverLicenseSubmissionStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_submission_persists_both_documents_and_returns_created(): void
    {
        Storage::fake('local');
        config(['services.cloudinary.enabled' => false]);

        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('license-test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/customer/license', $this->validSubmission($customer));

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', DriverLicense::STATUS_PENDING_REVIEW);

        $license = DriverLicense::query()->whereBelongsTo($customer)->sole();

        $this->assertNotNull($license->front_document_path);
        $this->assertNotNull($license->back_document_path);
        Storage::disk('local')->assertExists($license->front_document_path);
        Storage::disk('local')->assertExists($license->back_document_path);
    }

    public function test_storage_failure_returns_safe_retryable_response_without_creating_license(): void
    {
        config([
            'services.cloudinary.enabled' => false,
            'filesystems.disks.local.root' => '/proc/abay-license-storage-unavailable',
            'filesystems.disks.local.throw' => false,
        ]);
        Storage::forgetDisk('local');

        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('license-test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/customer/license', $this->validSubmission($customer));

        $response
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'LICENSE_DOCUMENT_STORAGE_UNAVAILABLE')
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('trace');

        $this->assertDatabaseCount('driver_licenses', 0);
    }

    public function test_event_listener_failure_does_not_change_a_committed_submission_to_an_error(): void
    {
        Storage::fake('local');
        config(['services.cloudinary.enabled' => false]);

        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('license-test')->plainTextToken;

        Event::listen(LicenseSubmitted::class, static function (): void {
            throw new \RuntimeException('Synthetic event listener failure.');
        });

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/customer/license', $this->validSubmission($customer));

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', DriverLicense::STATUS_PENDING_REVIEW);

        $this->assertDatabaseHas('driver_licenses', [
            'user_id' => $customer->id,
            'status' => DriverLicense::STATUS_PENDING_REVIEW,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validSubmission(User $customer): array
    {
        return [
            'document_type' => DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE,
            'document_number' => 'ETH-STORAGE-1234',
            'full_name' => $customer->name,
            'license_category' => DriverLicense::CATEGORY_AUTOMOBILE,
            'issue_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYears(4)->toDateString(),
            'front_document' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
            'back_document' => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
        ];
    }
}
