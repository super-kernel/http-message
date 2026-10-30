<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Provider;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface;
use SuperKernel\Attribute\Provider;
use SuperKernel\HttpMessage\Context\CurrentResponseCall;

use function func_get_args;

#[Provider(PsrResponseInterface::class)]
final readonly class ResponseProvider implements PsrResponseInterface
{
    public function getProtocolVersion(): string
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function getHeaders(): array
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function hasHeader(string $name): bool
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function getHeader(string $name): array
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function getHeaderLine(string $name): string
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function getBody(): StreamInterface
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function getStatusCode(): int
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function withStatus(int $code, string $reasonPhrase = ''): PsrResponseInterface
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }

    public function getReasonPhrase(): string
    {
        return CurrentResponseCall::forward(__FUNCTION__, func_get_args());
    }
}
