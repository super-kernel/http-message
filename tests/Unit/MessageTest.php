<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SuperKernel\HttpMessage\Response;
use SuperKernel\HttpMessage\Stream\StringStream;

final class MessageTest extends TestCase
{
    public function testProvidesImmutableProtocolHeadersAndBody(): void
    {
        $body = new StringStream('body');
        $message = new Response(
            headers: [
                'X-Trace' => 'one',
            ],
            body: $body,
        );

        $changed = $message
            ->withProtocolVersion('2')
            ->withAddedHeader('x-trace', 'two')
            ->withHeader('Content-Type', " text/plain\t");

        self::assertNotSame($message, $changed);
        self::assertSame('1.1', $message->getProtocolVersion());
        self::assertSame([
            'one',
        ], $message->getHeader('x-trace'));
        self::assertFalse($message->hasHeader('Content-Type'));
        self::assertSame('2', $changed->getProtocolVersion());
        self::assertSame([
            'one',
            'two',
        ], $changed->getHeader('X-TRACE'));
        self::assertSame('one, two', $changed->getHeaderLine('x-trace'));
        self::assertSame([
            'X-Trace' => [
                'one',
                'two',
            ],
            'Content-Type' => [
                'text/plain',
            ],
        ], $changed->getHeaders());
        self::assertSame($body, $changed->getBody());
        self::assertFalse($changed->withoutHeader('X-Trace')->hasHeader('x-trace'));
    }

    public function testRejectsInvalidProtocolAndHeaderDeclarations(): void
    {
        $message = new Response();

        try {
            $message->withProtocolVersion('HTTP/1.1');
            self::fail('The invalid protocol version was accepted.');
        } catch (InvalidArgumentException) {
        }

        try {
            $message->withHeader('Invalid Header', 'value');
            self::fail('The invalid header name was accepted.');
        } catch (InvalidArgumentException) {
        }

        try {
            $message->withHeader('X-Control', "value\0unsafe");
            self::fail('The invalid header control character was accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        $message->withHeader('X-Unsafe', "value\r\ninjected");
    }

    public function testProvidesDefaultAndCustomResponseReasons(): void
    {
        $response = new Response();
        $custom = $response->withStatus(299, 'Custom');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getReasonPhrase());
        self::assertSame(299, $custom->getStatusCode());
        self::assertSame('Custom', $custom->getReasonPhrase());
    }

    public function testRejectsInvalidResponseStatusAndReasonPhrase(): void
    {
        $response = new Response();

        try {
            $response->withStatus(99);
            self::fail('The invalid response status was accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        $response->withStatus(200, "Unsafe\0reason");
    }
}
