<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use SuperKernel\HttpMessage\Uri;

final class UriTest extends TestCase
{
    /**
     * The HTTP scheme and user information are literal URI parsing fixtures.
     * These assertions protect the public interface contract even when static types already imply it.
     *
     * @noinspection HttpUrlsUsage
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testParsesNormalizesAndRendersUriComponents(): void
    {
        $uri = new Uri('HTTP://user:pass@Example.COM:80/a%20path/%2F?q=a%20b#fragment');

        self::assertInstanceOf(UriInterface::class, $uri);
        self::assertSame('http', $uri->getScheme());
        self::assertSame('user:pass', $uri->getUserInfo());
        self::assertSame('example.com', $uri->getHost());
        self::assertNull($uri->getPort());
        self::assertSame('user:pass@example.com', $uri->getAuthority());
        self::assertSame('/a%20path/%2F', $uri->getPath());
        self::assertSame('q=a%20b', $uri->getQuery());
        self::assertSame('fragment', $uri->getFragment());
        self::assertSame(
            'http://user:pass@example.com/a%20path/%2F?q=a%20b#fragment',
            (string) $uri,
        );
    }

    public function testChangesComponentsWithoutDoubleEncoding(): void
    {
        $uri = new Uri();
        $changed = $uri
            ->withScheme('https')
            ->withHost('EXAMPLE.test')
            ->withPort(8443)
            ->withPath('widgets/a b/%2F')
            ->withQuery('?q=a b&encoded=%26')
            ->withFragment('#part one');

        self::assertSame('', (string) $uri);
        self::assertSame(
            'https://example.test:8443/widgets/a%20b/%2F?q=a%20b&encoded=%26#part%20one',
            (string) $changed,
        );
    }

    public function testSupportsIpv6AndTheCompletePortRange(): void
    {
        $uri = new Uri('http://[::1]:8080/');

        self::assertSame('[::1]', $uri->getHost());
        self::assertSame('[::1]:8080', $uri->getAuthority());
        self::assertSame(0, $uri->withPort(0)->getPort());
    }

    public function testRendersValidAuthorityAndPathCombinations(): void
    {
        self::assertSame(
            'https://example.test/widgets',
            (string) new Uri()->withScheme('https')->withHost('example.test')->withPath('widgets'),
        );
        self::assertSame('/widgets', (string) new Uri()->withPath('///widgets'));
    }

    public function testRejectsInvalidUriComponents(): void
    {
        $uri = new Uri();

        try {
            $uri->withScheme('1http');
            self::fail('The invalid scheme was accepted.');
        } catch (InvalidArgumentException) {
        }

        try {
            $uri->withHost('invalid host');
            self::fail('The invalid host was accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        $uri->withPort(65_536);
    }
}
