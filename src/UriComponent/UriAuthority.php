<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\UriComponent;

/**
 * Renders a URI authority from normalized components.
 *
 * @internal
 */
final class UriAuthority
{
    /**
     * @return string The authority with a non-standard port, or an empty string when the host is empty
     */
    public static function compose(string $scheme, string $userInfo, string $host, ?int $port): string
    {
        if ('' === $host) {
            return '';
        }

        $authority = '' === $userInfo ? '' : $userInfo . '@';
        $authority .= $host;
        $exposedPort = StandardPort::suppress($scheme, $port);

        return null === $exposedPort ? $authority : $authority . ':' . $exposedPort;
    }
}
