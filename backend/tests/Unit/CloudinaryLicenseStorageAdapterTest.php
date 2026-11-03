<?php

namespace Tests\Unit;

use App\Filesystem\CloudinaryLicenseStorageAdapter;
use Cloudinary\Cloudinary;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CloudinaryLicenseStorageAdapterTest extends TestCase
{
    #[DataProvider('resourcePaths')]
    public function test_it_maps_paths_to_cloudinary_resource_ids(
        string $path,
        string $expectedId,
        string $expectedType,
    ): void {
        $this->assertSame(
            [$expectedId, $expectedType],
            $this->adapter()->prepareResource($path),
        );
    }

    public function test_it_preserves_raw_extensions_after_applying_a_prefix(): void
    {
        $this->assertSame(
            ['tenant/licenses/42/front.pdf', 'raw'],
            $this->adapter('tenant')->prepareResource('licenses/42/front.pdf'),
        );
    }

    public static function resourcePaths(): array
    {
        return [
            'jpeg image' => [
                'licenses/42/front.jpg',
                'licenses/42/front',
                'image',
            ],
            'webp image' => [
                'licenses/42/front.webp',
                'licenses/42/front',
                'image',
            ],
            'pdf document' => [
                'licenses/42/front.pdf',
                'licenses/42/front.pdf',
                'raw',
            ],
        ];
    }

    private function adapter(?string $prefix = null): CloudinaryLicenseStorageAdapter
    {
        return new CloudinaryLicenseStorageAdapter(
            new Cloudinary([
                'cloud' => [
                    'cloud_name' => 'demo',
                    'api_key' => 'key',
                    'api_secret' => 'secret',
                ],
            ]),
            null,
            $prefix,
        );
    }
}
