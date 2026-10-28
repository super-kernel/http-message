<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use SuperKernel\HttpMessage\Stream\StringStream;

use function array_values;
use function implode;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function strtolower;
use function trim;

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
        $this->protocolVersion = self::normalizeProtocolVersion($protocolVersion);
        $this->body = $body instanceof StreamInterface ? $body : new StringStream($body ?? '');

        foreach ($headers as $name => $value) {
            if (!is_string($name)) {
                throw new InvalidArgumentException('HTTP header names must be strings.');
            }

            $this->replaceHeader($name, $value);
        }
    }

    final public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    final public function withProtocolVersion(string $version): static
    {
        $clone = clone $this;
        $clone->protocolVersion = self::normalizeProtocolVersion($version);

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
        $originalName = $this->headerNames[strtolower($name)] ?? null;

        return null === $originalName ? [] : $this->headers[$originalName];
    }

    final public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    final public function withHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $clone->replaceHeader($name, $value);

        return $clone;
    }

    final public function withAddedHeader(string $name, mixed $value): static
    {
        $normalizedName = self::normalizeHeaderName($name);
        $values = self::normalizeHeaderValues($value, $name);
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

    final protected function replaceHeader(string $name, mixed $value): void
    {
        $normalizedName = self::normalizeHeaderName($name);
        $originalName = $this->headerNames[$normalizedName] ?? null;

        if (null !== $originalName) {
            unset($this->headers[$originalName]);
        }

        $this->headers[$name] = self::normalizeHeaderValues($value, $name);
        $this->headerNames[$normalizedName] = $name;
    }

    private static function normalizeProtocolVersion(string $version): string
    {
        if (1 !== preg_match('/^[0-9]+(?:\.[0-9]+)*$/D', $version)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid HTTP protocol version %s.',
                $version,
            ));
        }

        return $version;
    }

    private static function normalizeHeaderName(string $name): string
    {
        if (1 !== preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/D", $name)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP header name %s.', $name));
        }

        return strtolower($name);
    }

    /**
     * @return list<string>
     */
    private static function normalizeHeaderValues(mixed $value, string $name): array
    {
        $values = is_array($value) ? array_values($value) : [
            $value,
        ];

        foreach ($values as $index => $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException(sprintf(
                    'HTTP header %s values must be strings.',
                    $name,
                ));
            }

            if (1 === preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $item)) {
                throw new InvalidArgumentException(sprintf(
                    'HTTP header %s contains a prohibited control character.',
                    $name,
                ));
            }

            $values[$index] = trim($item, " \t");
        }

        return $values;
    }
}
