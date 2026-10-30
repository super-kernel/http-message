<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Header;

use InvalidArgumentException;

/**
 * Replaces one header in a message header map while keeping the case-insensitive name index in sync.
 *
 * @internal
 */
final class HeaderMap
{
    /**
     * @param array<string, list<string>> $headers     Values keyed by original header name
     * @param array<string, string>       $headerNames Original header names keyed by lowercase name
     *
     * @throws InvalidArgumentException When the header name or a header value is invalid
     */
    public static function replace(array &$headers, array &$headerNames, string $name, mixed $value): void
    {
        $normalizedName = HeaderField::normalizeName($name);
        $originalName = $headerNames[$normalizedName] ?? null;

        if (null !== $originalName) {
            unset($headers[$originalName]);
        }

        $headers[$name] = HeaderField::normalizeValues($value, $name);
        $headerNames[$normalizedName] = $name;
    }
}
