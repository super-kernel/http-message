<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use SuperKernel\Context\Context;
use SuperKernel\HttpMessage\Context\ResponseContext;
use SuperKernel\HttpMessage\Response;

final class ResponseContextTest extends TestCase
{
    protected function tearDown(): void
    {
        ResponseContext::clear();
        Context::clear();

        parent::tearDown();
    }

    public function testStoresTheCurrentPsrResponse(): void
    {
        $response = new Response(statusCode: 202);

        self::assertFalse(ResponseContext::has());
        self::assertSame($response, ResponseContext::set($response));
        self::assertTrue(ResponseContext::has());
        self::assertInstanceOf(ResponseInterface::class, ResponseContext::get());
        self::assertSame($response, ResponseContext::get());

        ResponseContext::clear();
        self::assertFalse(ResponseContext::has());
    }

    public function testUsesThePsrInterfaceKeyWithoutClearingOtherContextValues(): void
    {
        $response = new Response(statusCode: 202);

        Context::set('application.value', 'preserved');
        ResponseContext::set($response);

        self::assertSame($response, Context::get(ResponseInterface::class));
        self::assertFalse(Context::has(ResponseContext::class));

        ResponseContext::clear();

        self::assertFalse(ResponseContext::has());
        self::assertFalse(Context::has(ResponseInterface::class));
        self::assertSame('preserved', Context::get('application.value'));
    }

    public function testRejectsAccessBeforeInitialization(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('has not been initialized');

        ResponseContext::get();
    }
}
