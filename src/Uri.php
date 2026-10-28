<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

use function array_key_exists;
use function filter_var;
use function is_string;
use function ltrim;
use function parse_url;
use function preg_match;
use function preg_replace_callback;
use function rawurlencode;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function substr;

use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

final class Uri implements UriInterface
{
    /** @var array<string, int> */
    private const array DEFAULT_PORTS = [
        'http'  => 80,
        'https' => 443,
    ];

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

        $this->scheme = self::normalizeScheme($parts['scheme'] ?? '');
        $this->host = self::normalizeHost($parts['host'] ?? '');
        $this->port = self::normalizePort($parts['port'] ?? null);
        $this->path = self::encodePath($parts['path'] ?? '');
        $this->query = self::encodeQueryOrFragment($parts['query'] ?? '');
        $this->fragment = self::encodeQueryOrFragment($parts['fragment'] ?? '');

        if (array_key_exists('user', $parts)) {
            $user = $parts['user'];

            if (!is_string($user)) {
                throw new InvalidArgumentException(sprintf('Unable to parse URI user information from %s.', $uri));
            }

            $password = $parts['pass'] ?? null;

            if (null !== $password && !is_string($password)) {
                throw new InvalidArgumentException(sprintf('Unable to parse URI password from %s.', $uri));
            }

            $this->userInfo = self::normalizeUserInfo($user, $password);
        }
    }

    private static function normalizeScheme(string $scheme): string
    {
        if ('' === $scheme) {
            return '';
        }

        if (1 !== preg_match('/^[A-Za-z][A-Za-z0-9+.-]*$/D', $scheme)) {
            throw new InvalidArgumentException(sprintf('Invalid URI scheme %s.', $scheme));
        }

        return strtolower($scheme);
    }

    private static function normalizeHost(string $host): string
    {
        if ('' === $host) {
            return '';
        }

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $address = substr($host, 1, -1);

            if (false === filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                throw new InvalidArgumentException(sprintf('Invalid URI host %s.', $host));
            }

            return '[' . strtolower($address) . ']';
        }

        if (false !== filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return '[' . strtolower($host) . ']';
        }

        if (1 !== preg_match("/^(?:[A-Za-z0-9._~!$&'()*+,;=-]|%[A-Fa-f0-9]{2})+$/D", $host)) {
            throw new InvalidArgumentException(sprintf('Invalid URI host %s.', $host));
        }

        return strtolower($host);
    }

    private static function normalizePort(?int $port): ?int
    {
        if (null !== $port && (0 > $port || 65_535 < $port)) {
            throw new InvalidArgumentException(sprintf('Invalid URI port %d.', $port));
        }

        return $port;
    }

    private static function encodePath(string $path): string
    {
        return self::encode(
            $path,
            "/[^a-zA-Z0-9_\-.~!$&'()*+,;=:@\/%]+|%(?![A-Fa-f0-9]{2})/",
        );
    }

    private static function encode(string $value, string $pattern): string
    {
        $encoded = preg_replace_callback(
            $pattern,
            static fn(array $matches): string => rawurlencode($matches[0]),
            $value,
        );

        if (null === $encoded) {
            throw new InvalidArgumentException('Unable to percent-encode a URI component.');
        }

        return $encoded;
    }

    private static function encodeQueryOrFragment(string $value): string
    {
        return self::encode(
            $value,
            "/[^a-zA-Z0-9_\-.~!$&'()*+,;=:@\/?%]+|%(?![A-Fa-f0-9]{2})/",
        );
    }

    private static function normalizeUserInfo(string $user, ?string $password): string
    {
        if ('' === $user) {
            return '';
        }

        $userInfo = self::encodeUserInfo($user);

        return null === $password ? $userInfo : $userInfo . ':' . self::encodeUserInfo($password);
    }

    private static function encodeUserInfo(string $value): string
    {
        return self::encode(
            $value,
            "/[^a-zA-Z0-9_\-.~!$&'()*+,;=:%]+|%(?![A-Fa-f0-9]{2})/",
        );
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
        $clone->scheme = self::normalizeScheme($scheme);

        return $clone;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $clone = clone $this;
        $clone->userInfo = self::normalizeUserInfo($user, $password);

        return $clone;
    }

    public function withHost(string $host): UriInterface
    {
        $clone = clone $this;
        $clone->host = self::normalizeHost($host);

        return $clone;
    }

    public function withPort(?int $port): UriInterface
    {
        $clone = clone $this;
        $clone->port = self::normalizePort($port);

        return $clone;
    }

    public function withPath(string $path): UriInterface
    {
        $clone = clone $this;
        $clone->path = self::encodePath($path);

        return $clone;
    }

    public function withQuery(string $query): UriInterface
    {
        if (str_starts_with($query, '?')) {
            $query = substr($query, 1);
        }

        $clone = clone $this;
        $clone->query = self::encodeQueryOrFragment($query);

        return $clone;
    }

    public function withFragment(string $fragment): UriInterface
    {
        if (str_starts_with($fragment, '#')) {
            $fragment = substr($fragment, 1);
        }

        $clone = clone $this;
        $clone->fragment = self::encodeQueryOrFragment($fragment);

        return $clone;
    }

    public function __toString(): string
    {
        $uri = '' === $this->scheme ? '' : $this->scheme . ':';
        $authority = $this->getAuthority();
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
        if ('' === $this->host) {
            return '';
        }

        $authority = '' === $this->userInfo ? '' : $this->userInfo . '@';
        $authority .= $this->host;
        $port = $this->getPort();

        return null === $port ? $authority : $authority . ':' . $port;
    }

    public function getPort(): ?int
    {
        if (null === $this->port) {
            return null;
        }

        return (self::DEFAULT_PORTS[$this->scheme] ?? null) === $this->port ? null : $this->port;
    }
}
