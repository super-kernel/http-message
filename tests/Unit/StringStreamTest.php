<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\StringStream;

use function fclose;
use function is_resource;

use const SEEK_END;

final class StringStreamTest extends TestCase
{
    public function testReadsWritesSeeksAndReportsMetadata(): void
    {
        $stream = new StringStream('payload');

        self::assertSame(7, $stream->getSize());
        self::assertSame(0, $stream->tell());
        self::assertSame('pay', $stream->read(3));
        self::assertTrue($stream->isReadable());
        self::assertTrue($stream->isWritable());
        self::assertTrue($stream->isSeekable());
        self::assertSame('php://temp', $stream->getMetadata('uri'));

        $stream->seek(0, SEEK_END);
        self::assertSame(1, $stream->write('!'));
        self::assertSame(8, $stream->getSize());
        self::assertSame('payload!', (string) $stream);
    }

    public function testDetachTransfersTheNativeResource(): void
    {
        $stream = new StringStream('payload');
        $resource = $stream->detach();

        self::assertTrue(is_resource($resource));
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertNull($stream->getSize());
        self::assertSame([], $stream->getMetadata());
        self::assertSame('', (string) $stream);

        try {
            $stream->tell();
            self::fail('The detached stream remained usable.');
        } catch (RuntimeException) {
        } finally {
            fclose($resource);
        }
    }

    public function testRejectsAnInvalidSeekModeWithRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        new StringStream()->seek(0, -1);
    }
}
