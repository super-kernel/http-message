<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Header;

use InvalidArgumentException;

use function array_values;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Validates HTTP header field names and values and normalizes them for the case-insensitive header map.
 *
 * @internal
 */
final class HeaderField
{
    /**
     * @return string The lowercase name used as the case-insensitive lookup key
     *
     * @throws InvalidArgumentException When the name is not an HTTP token
     */
    public static function normalizeName(string $name): string
    {
        if (1 !== preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/D", $name)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP header name %s.', $name));
        }

        return strtolower($name);
    }

    /**
     * @param string $name The header name reported in failures
     *
     * @return list<string> The values without surrounding spaces and horizontal tabs
     *
     * @throws InvalidArgumentException When a value is not a string or contains a prohibited control character
     */
    public static function normalizeValues(mixed $value, string $name): array
    {
        $values = is_array($value) ? array_values($value) : [
            $value,
        ];

        foreach ($values as $index => $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException(sprintf(
                    'HTTP header %s values must be strings.',
                    $name,
                ));
            }

            if (1 === preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $item)) {
                throw new InvalidArgumentException(sprintf(
                    'HTTP header %s contains a prohibited control character.',
                    $name,
                ));
            }

            $values[$index] = trim($item, " \t");
        }

        return $values;
    }
}
