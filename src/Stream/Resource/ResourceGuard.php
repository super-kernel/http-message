<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream\Resource;

use RuntimeException;

use function is_resource;

/**
 * Returns the native handle of a resource-backed stream while the stream is still attached.
 *
 * @internal
 */
final class ResourceGuard
{
    /**
     * @param resource|null $resource
     *
     * @return resource
     *
     * @throws RuntimeException When the stream is detached or closed
     */
    public static function requireAttached(mixed $resource): mixed
    {
        if (!is_resource($resource)) {
            throw new RuntimeException('The HTTP message stream is detached or closed.');
        }

        return $resource;
    }
}
