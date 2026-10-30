<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Upload;

use RuntimeException;

use function sprintf;

use const UPLOAD_ERR_OK;

/**
 * Guards access to an uploaded file after it was moved or when its upload failed.
 *
 * @internal
 */
final class UploadedFileGuard
{
    /**
     * @throws RuntimeException When the file has already been moved or the upload reported an error
     */
    public static function requireAvailable(bool $moved, int $error): void
    {
        if ($moved) {
            throw new RuntimeException('The uploaded file has already been moved.');
        }

        if (UPLOAD_ERR_OK !== $error) {
            throw new RuntimeException(sprintf(
                'The uploaded file is unavailable because upload error %d occurred.',
                $error,
            ));
        }
    }
}
