<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class LicenseDocumentStorageException extends RuntimeException
{
    public const ERROR_CODE = 'LICENSE_DOCUMENT_STORAGE_UNAVAILABLE';

    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('The license document could not be stored.', 0, $previous);
    }
}
