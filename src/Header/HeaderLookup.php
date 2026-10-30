<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Header;

use function strtolower;

/**
 * Resolves header values by case-insensitive name from a message header map.
 *
 * @internal
 */
final class HeaderLookup
{
    /**
     * @param array<string, list<string>> $headers     Values keyed by original header name
     * @param array<string, string>       $headerNames Original header names keyed by lowercase name
     *
     * @return list<string> The values, or an empty list when the header is absent
     */
    public static function values(array $headers, array $headerNames, string $name): array
    {
        $originalName = $headerNames[strtolower($name)] ?? null;

        return null === $originalName ? [] : $headers[$originalName];
    }
}
