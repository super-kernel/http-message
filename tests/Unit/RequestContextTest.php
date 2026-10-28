<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use Fiber;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use SuperKernel\Context\Context;
use SuperKernel\HttpMessage\Context\RequestContext;
use SuperKernel\HttpMessage\ServerRequest;
use Throwable;

final class RequestContextTest extends TestCase
{
    protected function tearDown(): void
    {
        RequestContext::clear();
        Context::clear();

        parent::tearDown();
    }

    public function testStoresTheCurrentPsrServerRequest(): void
    {
        $request = new ServerRequest(method: 'POST', uri: '/context');

        self::assertFalse(RequestContext::has());
        self::assertSame($request, RequestContext::set($request));
        self::assertTrue(RequestContext::has());
        self::assertInstanceOf(ServerRequestInterface::class, RequestContext::get());
        self::assertSame($request, RequestContext::get());

        RequestContext::clear();
        self::assertFalse(RequestContext::has());
    }

    public function testUsesThePsrInterfaceKeyWithoutClearingOtherContextValues(): void
    {
        $request = new ServerRequest('GET', '/shared-context');

        Context::set('application.value', 'preserved');
        RequestContext::set($request);

        self::assertSame($request, Context::get(ServerRequestInterface::class));
        self::assertFalse(Context::has(RequestContext::class));

        RequestContext::clear();

        self::assertFalse(RequestContext::has());
        self::assertFalse(Context::has(ServerRequestInterface::class));
        self::assertSame('preserved', Context::get('application.value'));
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testNativeFiberDoesNotCreateASeparateRequestContext(): void
    {
        $parent = new ServerRequest('GET', '/parent');
        $nested = new ServerRequest('GET', '/fiber');

        RequestContext::set($parent);

        $fiber = new Fiber(static function () use ($nested): ServerRequestInterface {
            self::assertTrue(RequestContext::has());
            self::assertSame('/parent', RequestContext::get()->getUri()->getPath());

            return RequestContext::set($nested);
        });

        $fiber->start();

        self::assertSame($nested, $fiber->getReturn());
        self::assertSame($nested, RequestContext::get());
    }

    public function testRejectsAccessBeforeInitialization(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('has not been initialized');

        RequestContext::get();
    }
}
