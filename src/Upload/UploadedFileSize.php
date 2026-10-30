<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Upload;

use Psr\Http\Message\StreamInterface;

use function filesize;
use function is_int;

/**
 * Detects the size of an uploaded file from its stream or its temporary file.
 *
 * @internal
 */
final class UploadedFileSize
{
    /**
     * @return int|null The stream size when a stream is given, otherwise the temporary file size, or null when unknown
     */
    public static function detect(?StreamInterface $stream, ?string $file): ?int
    {
        if (null !== $stream) {
            return $stream->getSize();
        }

        if (null === $file) {
            return null;
        }

        $size = @filesize($file);

        return is_int($size) ? $size : null;
    }
}
