<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\UriComponent;

use InvalidArgumentException;

use function filter_var;
use function preg_match;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function substr;

use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

/**
 * Validates and normalizes raw URI components for Uri.
 *
 * @internal
 */
final class UriComponents
{
    private const string PATH_PATTERN = "/[^a-zA-Z0-9_\-.~!$&'()*+,;=:@\/%]+|%(?![A-Fa-f0-9]{2})/";

    private const string QUERY_OR_FRAGMENT_PATTERN = "/[^a-zA-Z0-9_\-.~!$&'()*+,;=:@\/?%]+|%(?![A-Fa-f0-9]{2})/";

    private const string USER_INFO_PATTERN = "/[^a-zA-Z0-9_\-.~!$&'()*+,;=:%]+|%(?![A-Fa-f0-9]{2})/";

    /**
     * @return string The lowercase scheme, or an empty string when none is given
     *
     * @throws InvalidArgumentException When a non-empty scheme does not match the URI scheme syntax
     */
    public static function normalizeScheme(string $scheme): string
    {
        if ('' === $scheme) {
            return '';
        }

        if (1 !== preg_match('/^[A-Za-z][A-Za-z0-9+.-]*$/D', $scheme)) {
            throw new InvalidArgumentException(sprintf('Invalid URI scheme %s.', $scheme));
        }

        return strtolower($scheme);
    }

    /**
     * @return string The lowercase host with IPv6 addresses enclosed in brackets, or an empty string
     *
     * @throws InvalidArgumentException When a non-empty host is neither an IPv6 address nor a registered name
     */
    public static function normalizeHost(string $host): string
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

    /**
     * @throws InvalidArgumentException When the port is outside 0..65535
     */
    public static function normalizePort(?int $port): ?int
    {
        if (null !== $port && (0 > $port || 65_535 < $port)) {
            throw new InvalidArgumentException(sprintf('Invalid URI port %d.', $port));
        }

        return $port;
    }

    /**
     * @throws InvalidArgumentException When PCRE fails while encoding the path
     */
    public static function encodePath(string $path): string
    {
        return UriPercentEncoder::encode($path, self::PATH_PATTERN);
    }

    /**
     * @throws InvalidArgumentException When PCRE fails while encoding the query or fragment
     */
    public static function encodeQueryOrFragment(string $value): string
    {
        return UriPercentEncoder::encode($value, self::QUERY_OR_FRAGMENT_PATTERN);
    }

    /**
     * @return string The encoded user and optional password, or an empty string when the user is empty
     *
     * @throws InvalidArgumentException When PCRE fails while encoding the user or password
     */
    public static function normalizeUserInfo(string $user, ?string $password): string
    {
        if ('' === $user) {
            return '';
        }

        $userInfo = UriPercentEncoder::encode($user, self::USER_INFO_PATTERN);

        return null === $password
            ? $userInfo
            : $userInfo . ':' . UriPercentEncoder::encode($password, self::USER_INFO_PATTERN);
    }
}
