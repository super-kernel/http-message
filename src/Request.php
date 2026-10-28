<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

use function preg_match;
use function sprintf;

class Request extends Message implements RequestInterface
{
    private string $method;

    private UriInterface $uri;

    private ?string $requestTarget = null;

    /**
     * @param array<string, string|string[]> $headers
     *
     * @throws InvalidArgumentException When the method, URI, protocol version, or a header is invalid
     */
    public function __construct(
        string                      $method,
        UriInterface|string         $uri,
        array                       $headers = [],
        StreamInterface|string|null $body = null,
        string                      $protocolVersion = '1.1',
    )
    {
        parent::__construct($headers, $body, $protocolVersion);

        $this->method = self::normalizeMethod($method);
        $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);

        if (!$this->hasHeader('Host') && '' !== $this->uri->getHost()) {
            $this->replaceHeader('Host', self::hostHeader($this->uri));
        }
    }

    final public function getRequestTarget(): string
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();

        if ('' === $target) {
            $target = '/';
        }

        $query = $this->uri->getQuery();

        return '' === $query ? $target : $target . '?' . $query;
    }

    final public function withRequestTarget(string $requestTarget): static
    {
        if ('' === $requestTarget || 1 === preg_match('/[\x00-\x20\x7F]/', $requestTarget)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid HTTP request target %s.',
                $requestTarget,
            ));
        }

        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    final public function getMethod(): string
    {
        return $this->method;
    }

    final public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->method = self::normalizeMethod($method);

        return $clone;
    }

    final public function getUri(): UriInterface
    {
        return $this->uri;
    }

    final public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if (
            '' !== $uri->getHost()
            && (!$preserveHost || '' === $this->getHeaderLine('Host'))
        ) {
            $clone->replaceHeader('Host', self::hostHeader($uri));
        }

        return $clone;
    }

    private static function normalizeMethod(string $method): string
    {
        if (1 !== preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/D", $method)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP method %s.', $method));
        }

        return $method;
    }

    private static function hostHeader(UriInterface $uri): string
    {
        $port = $uri->getPort();

        return null === $port ? $uri->getHost() : $uri->getHost() . ':' . $port;
    }
}
