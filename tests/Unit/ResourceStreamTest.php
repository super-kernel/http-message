<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SuperKernel\HttpMessage\Stream\ResourceStream;

use function fclose;
use function fopen;
use function fwrite;
use function is_resource;
use function rewind;

final class ResourceStreamTest extends TestCase
{
    public function testWrapsAndTransfersANativeStreamResource(): void
    {
        $resource = fopen('php://temp', 'r+b');
        self::assertNotFalse($resource);
        self::assertSame(7, fwrite($resource, 'payload'));
        self::assertTrue(rewind($resource));
        $stream = new ResourceStream($resource);

        try {
            self::assertTrue($stream->isReadable());
            self::assertTrue($stream->isWritable());
            self::assertTrue($stream->isSeekable());
            self::assertSame(7, $stream->getSize());
            self::assertSame('payload', $stream->getContents());
            self::assertSame($resource, $stream->detach());
            self::assertFalse($stream->isReadable());
            self::assertSame('', (string) $stream);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    public function testRejectsValuesThatAreNotNativeStreamResources(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ResourceStream('not a resource');
    }
}
