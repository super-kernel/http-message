<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\UriComponent;

/**
 * Suppresses the standard HTTP and HTTPS ports from exposed URI ports.
 *
 * @internal
 */
final class StandardPort
{
    /** @var array<string, int> */
    private const array DEFAULT_PORTS = [
        'http'  => 80,
        'https' => 443,
    ];

    /**
     * @return int|null The port, or null when it is absent or standard for the scheme
     */
    public static function suppress(string $scheme, ?int $port): ?int
    {
        if (null === $port) {
            return null;
        }

        return (self::DEFAULT_PORTS[$scheme] ?? null) === $port ? null : $port;
    }
}
