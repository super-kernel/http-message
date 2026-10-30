<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use SuperKernel\HttpMessage\Header\HeaderField;
use SuperKernel\HttpMessage\Header\HeaderLookup;
use SuperKernel\HttpMessage\StartLine\ProtocolVersion;
use SuperKernel\HttpMessage\Stream\StringStream;

use function implode;
use function is_string;
use function strtolower;

abstract class Message implements MessageInterface
{
    private string $protocolVersion;

    /** @var array<string, list<string>> */
    private array $headers = [];

    /** @var array<string, string> */
    private array $headerNames = [];

    private StreamInterface $body;

    /**
     * @param array<string, string|string[]> $headers
     *
     * @throws InvalidArgumentException When the protocol version, a header name, or a header value is invalid
     */
    protected function __construct(
        array                       $headers = [],
        StreamInterface|string|null $body = null,
        string                      $protocolVersion = '1.1',
    )
    {
        $this->protocolVersion = ProtocolVersion::normalize($protocolVersion);
        $this->body = $body instanceof StreamInterface ? $body : new StringStream($body ?? '');

        foreach ($headers as $name => $value) {
            if (!is_string($name)) {
                throw new InvalidArgumentException('HTTP header names must be strings.');
            }

            $normalizedName = HeaderField::normalizeName($name);
            $originalName = $this->headerNames[$normalizedName] ?? null;

            if (null !== $originalName) {
                unset($this->headers[$originalName]);
            }

            $this->headers[$name] = HeaderField::normalizeValues($value, $name);
            $this->headerNames[$normalizedName] = $name;
        }
    }

    final public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    final public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        $clone->protocolVersion = ProtocolVersion::normalize($version);

        return $clone;
    }

    /**
     * @return array<string, list<string>>
     */
    final public function getHeaders(): array
    {
        return $this->headers;
    }

    final public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * @return list<string>
     */
    final public function getHeader(string $name): array
    {
        return HeaderLookup::values($this->headers, $this->headerNames, $name);
    }

    final public function getHeaderLine(string $name): string
    {
        return implode(', ', HeaderLookup::values($this->headers, $this->headerNames, $name));
    }

    final public function withHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $normalizedName = HeaderField::normalizeName($name);
        $originalName = $clone->headerNames[$normalizedName] ?? null;

        if (null !== $originalName) {
            unset($clone->headers[$originalName]);
        }

        $clone->headers[$name] = HeaderField::normalizeValues($value, $name);
        $clone->headerNames[$normalizedName] = $name;

        return $clone;
    }

    final public function withAddedHeader(string $name, mixed $value): static
    {
        $normalizedName = HeaderField::normalizeName($name);
        $values = HeaderField::normalizeValues($value, $name);
        $clone = clone $this;
        $originalName = $clone->headerNames[$normalizedName] ?? null;

        if (null === $originalName) {
            $clone->headers[$name] = $values;
            $clone->headerNames[$normalizedName] = $name;

            return $clone;
        }

        $clone->headers[$originalName] = [
            ...$clone->headers[$originalName],
            ...$values,
        ];

        return $clone;
    }

    final public function withoutHeader(string $name): static
    {
        $normalizedName = strtolower($name);
        $originalName = $this->headerNames[$normalizedName] ?? null;

        if (null === $originalName) {
            return clone $this;
        }

        $clone = clone $this;
        unset($clone->headers[$originalName], $clone->headerNames[$normalizedName]);

        return $clone;
    }

    final public function getBody(): StreamInterface
    {
        return $this->body;
    }

    final public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * @throws InvalidArgumentException When the header name or a header value is invalid
     */
    final protected function replaceHeader(string $name, mixed $value): void
    {
        $normalizedName = HeaderField::normalizeName($name);
        $originalName = $this->headerNames[$normalizedName] ?? null;

        if (null !== $originalName) {
            unset($this->headers[$originalName]);
        }

        $this->headers[$name] = HeaderField::normalizeValues($value, $name);
        $this->headerNames[$normalizedName] = $name;
    }
}
