<?php

namespace App\Providers;

use App\Filesystem\CloudinaryLicenseStorageAdapter;
use Cloudinary\Cloudinary;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class CloudinaryLicenseFilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['filesystem']->extend('cloudinary-license', function ($app, array $config) {
            $cloudinary = filled($config['url'] ?? null)
                ? new Cloudinary($config['url'])
                : new Cloudinary([
                    'cloud' => [
                        'cloud_name' => $config['cloud'],
                        'api_key' => $config['key'],
                        'api_secret' => $config['secret'],
                    ],
                    'url' => [
                        'secure' => $config['secure'] ?? false,
                    ],
                ]);

            $adapter = new CloudinaryLicenseStorageAdapter(
                $cloudinary,
                null,
                $config['prefix'] ?? null,
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config,
            );
        });
    }
}
