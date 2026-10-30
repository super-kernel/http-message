<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\StartLine;

use InvalidArgumentException;

use function preg_match;
use function sprintf;

/**
 * Validates HTTP protocol versions for messages.
 *
 * @internal
 */
final class ProtocolVersion
{
    /**
     * @throws InvalidArgumentException When the version is not a dot-separated sequence of decimal numbers
     */
    public static function normalize(string $version): string
    {
        if (1 !== preg_match('/^[0-9]+(?:\.[0-9]+)*$/D', $version)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid HTTP protocol version %s.',
                $version,
            ));
        }

        return $version;
    }
}
