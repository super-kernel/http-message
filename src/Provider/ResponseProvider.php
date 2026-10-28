<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Provider;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface;
use SuperKernel\Attribute\Provider;
use SuperKernel\HttpMessage\Context\ResponseContext;

use function func_get_args;

#[Provider(PsrResponseInterface::class)]
final readonly class ResponseProvider implements PsrResponseInterface
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

    public function getStatusCode(): int
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function withStatus(int $code, string $reasonPhrase = ''): PsrResponseInterface
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function getReasonPhrase(): string
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    private function delegate(string $name, array $arguments): mixed
    {
        return $this->response()->{$name}(...$arguments);
    }

    private function response(): PsrResponseInterface
    {
        return ResponseContext::get();
    }
}
