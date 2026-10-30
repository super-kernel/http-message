<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use SuperKernel\HttpMessage\UriComponent\StandardPort;
use SuperKernel\HttpMessage\UriComponent\UriAuthority;
use SuperKernel\HttpMessage\UriComponent\UriComponents;

use function array_key_exists;
use function is_string;
use function ltrim;
use function parse_url;
use function sprintf;
use function str_starts_with;
use function substr;

final class Uri implements UriInterface
{
    private string $scheme = '';

    private string $userInfo = '';

    private string $host = '';

    private ?int $port = null;

    private string $path = '';

    private string $query = '';

    private string $fragment = '';

    /**
     * @throws InvalidArgumentException When the URI cannot be parsed or contains an invalid component
     */
    public function __construct(string $uri = '')
    {
        if ('' === $uri) {
            return;
        }

        $parts = parse_url($uri);

        if (false === $parts) {
            throw new InvalidArgumentException(sprintf('Unable to parse URI %s.', $uri));
        }

        $this->scheme = UriComponents::normalizeScheme($parts['scheme'] ?? '');
        $this->host = UriComponents::normalizeHost($parts['host'] ?? '');
        $this->port = UriComponents::normalizePort($parts['port'] ?? null);
        $this->path = UriComponents::encodePath($parts['path'] ?? '');
        $this->query = UriComponents::encodeQueryOrFragment($parts['query'] ?? '');
        $this->fragment = UriComponents::encodeQueryOrFragment($parts['fragment'] ?? '');

        if (array_key_exists('user', $parts)) {
            $user = $parts['user'];

            if (!is_string($user)) {
                throw new InvalidArgumentException(sprintf('Unable to parse URI user information from %s.', $uri));
            }

            $password = $parts['pass'] ?? null;

            if (null !== $password && !is_string($password)) {
                throw new InvalidArgumentException(sprintf('Unable to parse URI password from %s.', $uri));
            }

            $this->userInfo = UriComponents::normalizeUserInfo($user, $password);
        }
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $clone = clone $this;
        $clone->scheme = UriComponents::normalizeScheme($scheme);

        return $clone;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $clone = clone $this;
        $clone->userInfo = UriComponents::normalizeUserInfo($user, $password);

        return $clone;
    }

    public function withHost(string $host): UriInterface
    {
        $clone = clone $this;
        $clone->host = UriComponents::normalizeHost($host);

        return $clone;
    }

    public function withPort(?int $port): UriInterface
    {
        $clone = clone $this;
        $clone->port = UriComponents::normalizePort($port);

        return $clone;
    }

    public function withPath(string $path): UriInterface
    {
        $clone = clone $this;
        $clone->path = UriComponents::encodePath($path);

        return $clone;
    }

    public function withQuery(string $query): UriInterface
    {
        if (str_starts_with($query, '?')) {
            $query = substr($query, 1);
        }

        $clone = clone $this;
        $clone->query = UriComponents::encodeQueryOrFragment($query);

        return $clone;
    }

    public function withFragment(string $fragment): UriInterface
    {
        if (str_starts_with($fragment, '#')) {
            $fragment = substr($fragment, 1);
        }

        $clone = clone $this;
        $clone->fragment = UriComponents::encodeQueryOrFragment($fragment);

        return $clone;
    }

    public function __toString(): string
    {
        $uri = '' === $this->scheme ? '' : $this->scheme . ':';
        $authority = UriAuthority::compose($this->scheme, $this->userInfo, $this->host, $this->port);
        $path = $this->path;

        if ('' !== $authority) {
            $uri .= '//' . $authority;

            if ('' !== $path && !str_starts_with($path, '/')) {
                $path = '/' . $path;
            }
        } elseif (str_starts_with($path, '//')) {
            $path = '/' . ltrim($path, '/');
        }

        $uri .= $path;

        if ('' !== $this->query) {
            $uri .= '?' . $this->query;
        }

        if ('' !== $this->fragment) {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    public function getAuthority(): string
    {
        return UriAuthority::compose($this->scheme, $this->userInfo, $this->host, $this->port);
    }

    public function getPort(): ?int
    {
        return StandardPort::suppress($this->scheme, $this->port);
    }
}
