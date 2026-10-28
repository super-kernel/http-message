<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Provider;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use SuperKernel\Attribute\Provider;
use SuperKernel\HttpMessage\Context\RequestContext;

use function func_get_args;

#[Provider(ServerRequestInterface::class)]
final readonly class RequestProvider implements ServerRequestInterface
{
    public function getProtocolVersion(): string
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getHeaders(): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function hasHeader(string $name): bool
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getHeader(string $name): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getHeaderLine(string $name): string
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getBody(): StreamInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getRequestTarget(): string
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withRequestTarget(string $requestTarget): PsrRequestInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getMethod(): string
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withMethod(string $method): PsrRequestInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getUri(): UriInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): PsrRequestInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getServerParams(): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getCookieParams(): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getQueryParams(): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getUploadedFiles(): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getParsedBody()
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getAttributes(): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getAttribute(string $name, $default = null)
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    private function delegate(string $name, array $arguments): mixed
    {
        return $this->request()->{$name}(...$arguments);
    }

    private function request(): ServerRequestInterface
    {
        return RequestContext::get();
    }
}
