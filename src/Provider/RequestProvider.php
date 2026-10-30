<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Provider;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use SuperKernel\Attribute\Provider;
use SuperKernel\HttpMessage\Context\CurrentRequestCall;

use function func_get_args;

#[Provider(ServerRequestInterface::class)]
final readonly class RequestProvider implements ServerRequestInterface
{
    public function getProtocolVersion(): string
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getHeaders(): array
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function hasHeader(string $name): bool
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getHeader(string $name): array
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getHeaderLine(string $name): string
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getBody(): StreamInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getRequestTarget(): string
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withRequestTarget(string $requestTarget): PsrRequestInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getMethod(): string
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withMethod(string $method): PsrRequestInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getUri(): UriInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): PsrRequestInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getServerParams(): array
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getCookieParams(): array
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getQueryParams(): array
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getUploadedFiles(): array
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getParsedBody()
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getAttributes(): array
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function getAttribute(string $name, $default = null)
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        return CurrentRequestCall::forward(__FUNCTION__, func_get_args());
    }
}
