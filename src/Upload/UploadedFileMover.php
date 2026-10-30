<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Upload;

use RuntimeException;
use SuperKernel\HttpMessage\Stream\FileStream;
use Throwable;

use function is_uploaded_file;
use function move_uploaded_file;
use function rename;
use function sprintf;
use function unlink;

/**
 * Moves a temporary upload file to its target path, falling back to copying and removing the source.
 *
 * @internal
 */
final class UploadedFileMover
{
    /**
     * @throws Throwable When moving, copying, or removing the temporary source fails
     */
    public static function move(string $sourcePath, string $targetPath): void
    {
        $moved = is_uploaded_file($sourcePath)
            ? @move_uploaded_file($sourcePath, $targetPath)
            : @rename($sourcePath, $targetPath);

        if ($moved) {
            return;
        }

        $source = new FileStream($sourcePath);

        try {
            UploadedFileCopier::copy($source, $targetPath);
        } catch (Throwable $throwable) {
            $source->close();
            @unlink($targetPath);

            throw $throwable;
        }

        $source->close();

        if (!@unlink($sourcePath)) {
            @unlink($targetPath);

            throw new RuntimeException(sprintf(
                'Unable to remove uploaded file source %s after copying.',
                $sourcePath,
            ));
        }
    }
}
