<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Context;

use RuntimeException;

/**
 * Forwards a declared PSR server-request call to the request stored in the current execution context.
 *
 * @internal
 */
final class CurrentRequestCall
{
    /**
     * @param array<array-key, mixed> $arguments
     *
     * @throws RuntimeException When the current HTTP request context has not been initialized
     */
    public static function forward(string $name, array $arguments): mixed
    {
        return RequestContext::get()->{$name}(...$arguments);
    }
}
