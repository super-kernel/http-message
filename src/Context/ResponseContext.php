<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Context;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use SuperKernel\Context\Context;

final class ResponseContext
{
    /**
     * @throws RuntimeException When the current response context has not been initialized
     */
    public static function get(): ResponseInterface
    {
        $response = Context::get(ResponseInterface::class);

        if (!$response instanceof ResponseInterface) {
            throw new RuntimeException('The current HTTP response context has not been initialized.');
        }

        return $response;
    }

    public static function set(ResponseInterface $response): ResponseInterface
    {
        return Context::set(ResponseInterface::class, $response);
    }

    public static function has(): bool
    {
        return Context::has(ResponseInterface::class);
    }

    public static function clear(): void
    {
        Context::clear(ResponseInterface::class);
    }
}
