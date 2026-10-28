<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\FileStream;

use function file_exists;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const SEEK_END;

final class FileStreamTest extends TestCase
{
    public function testOpensAFileWithTheRequestedMode(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'super-kernel-http-message-stream-');
        self::assertNotFalse($path);
        self::assertSame(7, file_put_contents($path, 'payload'));
        $stream = new FileStream($path, 'r+b');

        try {
            self::assertTrue($stream->isReadable());
            self::assertTrue($stream->isWritable());
            self::assertTrue($stream->isSeekable());
            self::assertSame(7, $stream->getSize());
            self::assertSame($path, $stream->getMetadata('uri'));
            $stream->seek(0, SEEK_END);
            self::assertSame(1, $stream->write('!'));
            self::assertSame('payload!', (string) $stream);
        } finally {
            $stream->close();

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testRejectsAFileThatCannotBeOpened(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'super-kernel-http-message-missing-');
        self::assertNotFalse($path);
        self::assertTrue(unlink($path));

        $this->expectException(RuntimeException::class);

        new FileStream($path);
    }
}
