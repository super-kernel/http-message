<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\StartLine;

use InvalidArgumentException;

use function preg_match;
use function sprintf;

/**
 * Validates case-sensitive HTTP request methods.
 *
 * @internal
 */
final class RequestMethod
{
    /**
     * @throws InvalidArgumentException When the method is not an HTTP token
     */
    public static function normalize(string $method): string
    {
        if (1 !== preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/D", $method)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP method %s.', $method));
        }

        return $method;
    }
}
