<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use SuperKernel\HttpMessage\Request;
use SuperKernel\HttpMessage\Uri;

final class RequestTest extends TestCase
{
    /**
     * These assertions protect the public interface contract even when static types already imply it.
     *
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testBuildsRequestTargetAndHostFromTheUri(): void
    {
        $request = new Request('GET', 'https://Example.test:8443/widgets?q=one');

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('GET', $request->getMethod());
        self::assertSame('/widgets?q=one', $request->getRequestTarget());
        self::assertSame('example.test:8443', $request->getHeaderLine('host'));
    }

    public function testChangesUriHostAccordingToPreservationRules(): void
    {
        $request = new Request('GET', 'https://first.test/path', [
            'Host' => 'explicit.test',
        ]);
        $uri = new Uri('https://second.test/other');

        $replaced = $request->withUri($uri);
        $preserved = $request->withUri($uri, true);

        self::assertSame('explicit.test', $request->getHeaderLine('Host'));
        self::assertSame('second.test', $replaced->getHeaderLine('Host'));
        self::assertSame('explicit.test', $preserved->getHeaderLine('Host'));
        self::assertSame('first.test', $request->getUri()->getHost());
    }

    public function testPreservesMethodAndCustomTargetCase(): void
    {
        $request = new Request('custom', '');
        $changed = $request->withMethod('PATCH')->withRequestTarget('*');

        self::assertSame('custom', $request->getMethod());
        self::assertSame('/', $request->getRequestTarget());
        self::assertSame('PATCH', $changed->getMethod());
        self::assertSame('*', $changed->getRequestTarget());
    }

    public function testRejectsInvalidMethodsAndTargets(): void
    {
        try {
            new Request('invalid method', '/');
            self::fail('The invalid method was accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        new Request('GET', '/')->withRequestTarget("/invalid\ntarget");
    }
}
