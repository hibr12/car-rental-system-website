<?php

namespace App\Filesystem;

use CloudinaryLabs\CloudinaryLaravel\CloudinaryStorageAdapter;

class CloudinaryLicenseStorageAdapter extends CloudinaryStorageAdapter
{
    /**
     * Cloudinary keeps the extension as part of a raw asset's public ID.
     * The upstream Flysystem adapter strips it for every resource type, which
     * makes uploaded PDFs impossible to find, read, or delete afterwards.
     *
     * @return array{0: string, 1: string}
     */
    public function prepareResource(string $path): array
    {
        [$id, $type] = parent::prepareResource($path);

        if ($type !== 'raw') {
            return [$id, $type];
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension !== '' && ! str_ends_with($id, '.'.$extension)) {
            $id .= '.'.$extension;
        }

        return [$id, $type];
    }
}
