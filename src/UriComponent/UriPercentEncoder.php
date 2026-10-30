<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\UriComponent;

use InvalidArgumentException;

use function preg_replace_callback;
use function rawurlencode;

/**
 * Percent-encodes the byte runs of a URI component that a component-specific pattern disallows.
 *
 * @internal
 */
final class UriPercentEncoder
{
    /**
     * @param string $pattern A PCRE pattern that matches disallowed byte runs and percent signs without two hex digits
     *
     * @throws InvalidArgumentException When PCRE fails while encoding the value
     */
    public static function encode(string $value, string $pattern): string
    {
        $encoded = preg_replace_callback(
            $pattern,
            static fn(array $matches): string => rawurlencode($matches[0]),
            $value,
        );

        if (null === $encoded) {
            throw new InvalidArgumentException('Unable to percent-encode a URI component.');
        }

        return $encoded;
    }
}
