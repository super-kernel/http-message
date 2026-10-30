<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Context;

use RuntimeException;

/**
 * Forwards a declared PSR response call to the response stored in the current execution context.
 *
 * @internal
 */
final class CurrentResponseCall
{
    /**
     * @param array<array-key, mixed> $arguments
     *
     * @throws RuntimeException When the current HTTP response context has not been initialized
     */
    public static function forward(string $name, array $arguments): mixed
    {
        return ResponseContext::get()->{$name}(...$arguments);
    }
}
