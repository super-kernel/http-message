<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\ReadOnlyStream;
use SuperKernel\HttpMessage\Stream\StringStream;

use function fclose;
use function is_resource;

final class ReadOnlyStreamTest extends TestCase
{
    public function testDelegatesReadAndSeekOperationsWhileRejectingWrites(): void
    {
        $stream = new ReadOnlyStream(new StringStream('payload'));

        self::assertTrue($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertTrue($stream->isSeekable());
        self::assertSame(7, $stream->getSize());
        self::assertSame('php://temp', $stream->getMetadata('uri'));
        self::assertSame('pay', $stream->read(3));
        $stream->rewind();
        self::assertSame('payload', $stream->getContents());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('read-only');

        $stream->write('!');
    }

    public function testDetachTransfersTheDecoratedNativeResource(): void
    {
        $decorated = new StringStream('payload');
        $stream = new ReadOnlyStream($decorated);
        $resource = $stream->detach();

        self::assertTrue(is_resource($resource));
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertFalse($decorated->isReadable());
        self::assertSame([], $stream->getMetadata());

        fclose($resource);
    }
}
