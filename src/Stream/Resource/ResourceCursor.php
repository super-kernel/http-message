<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream\Resource;

use RuntimeException;

use function fseek;
use function in_array;
use function is_resource;
use function sprintf;

use const SEEK_CUR;
use const SEEK_END;
use const SEEK_SET;

/**
 * Moves the cursor of a resource-backed stream after validating the seek mode and the stream capability.
 *
 * @internal
 */
final class ResourceCursor
{
    /**
     * @param resource|null $resource
     * @param bool          $seekable Whether the stream reported itself seekable when it was attached
     *
     * @throws RuntimeException When the seek mode is invalid, the stream is not seekable, or the seek fails
     */
    public static function seek(mixed $resource, bool $seekable, int $offset, int $whence): void
    {
        if (!in_array($whence, [
            SEEK_SET,
            SEEK_CUR,
            SEEK_END,
        ], true)) {
            throw new RuntimeException(sprintf(
                'Invalid HTTP message stream seek mode %d.',
                $whence,
            ));
        }

        if (!is_resource($resource) || !$seekable) {
            throw new RuntimeException('The HTTP message stream is not seekable.');
        }

        if (0 !== @fseek($resource, $offset, $whence)) {
            throw new RuntimeException(sprintf(
                'Unable to seek the HTTP message stream to offset %d.',
                $offset,
            ));
        }
    }
}
