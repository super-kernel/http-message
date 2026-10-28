<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Context;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use SuperKernel\Context\Context;

final class RequestContext
{
    /**
     * @throws RuntimeException When the current request context has not been initialized
     */
    public static function get(): ServerRequestInterface
    {
        $request = Context::get(ServerRequestInterface::class);

        if (!$request instanceof ServerRequestInterface) {
            throw new RuntimeException('The current HTTP request context has not been initialized.');
        }

        return $request;
    }

    public static function set(ServerRequestInterface $request): ServerRequestInterface
    {
        return Context::set(ServerRequestInterface::class, $request);
    }

    public static function has(): bool
    {
        return Context::has(ServerRequestInterface::class);
    }

    public static function clear(): void
    {
        Context::clear(ServerRequestInterface::class);
    }
}
