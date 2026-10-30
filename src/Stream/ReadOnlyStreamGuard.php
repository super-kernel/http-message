<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Returns the stream decorated by a read-only stream while the decorator is still attached.
 *
 * @internal
 */
final class ReadOnlyStreamGuard
{
    /**
     * @throws RuntimeException When the read-only stream is detached or closed
     */
    public static function requireAttached(?StreamInterface $stream): StreamInterface
    {
        if (null === $stream) {
            throw new RuntimeException('The read-only stream is detached or closed.');
        }

        return $stream;
    }
}
