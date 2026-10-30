<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream\Resource;

use RuntimeException;

use function is_resource;
use function stream_get_contents;

/**
 * Reads the remaining contents of a resource-backed stream.
 *
 * @internal
 */
final class ResourceContents
{
    /**
     * @param resource|null $resource
     * @param bool          $readable Whether the stream mode allows reading
     *
     * @throws RuntimeException When the stream is not readable or its remaining contents cannot be read
     */
    public static function remaining(mixed $resource, bool $readable): string
    {
        if (!is_resource($resource) || !$readable) {
            throw new RuntimeException('The HTTP message stream is not readable.');
        }

        $contents = @stream_get_contents($resource);

        if (false === $contents) {
            throw new RuntimeException('Unable to read the remaining HTTP message stream contents.');
        }

        return $contents;
    }
}
