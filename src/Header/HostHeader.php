<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Header;

use Psr\Http\Message\UriInterface;

/**
 * Derives the Host header value from a request URI.
 *
 * @internal
 */
final class HostHeader
{
    public static function forUri(UriInterface $uri): string
    {
        $port = $uri->getPort();

        return null === $port ? $uri->getHost() : $uri->getHost() . ':' . $port;
    }
}
