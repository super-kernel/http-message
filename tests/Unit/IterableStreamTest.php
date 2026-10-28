<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\IterableStream;

final class IterableStreamTest extends TestCase
{
    public function testReturnsAvailableBytesWithoutFillingTheRequestedLength(): void
    {
        $stream = new IterableStream([
            'ab',
            '',
            'cdef',
        ]);

        self::assertTrue($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertNull($stream->getSize());
        self::assertSame([], $stream->getMetadata());
        self::assertSame('ab', $stream->read(3));
        self::assertSame(2, $stream->tell());
        self::assertFalse($stream->eof());
        self::assertSame('cde', $stream->read(3));
        self::assertSame(5, $stream->tell());
        self::assertSame('f', $stream->getContents());
        self::assertSame(6, $stream->tell());
        self::assertTrue($stream->eof());
        self::assertSame('', $stream->read(1));
    }

    public function testReadReturnsTheProducedChunkWithoutPullingTheNextOne(): void
    {
        $pulled = 0;
        $stream = new IterableStream((static function () use (&$pulled): Generator {
            ++$pulled;

            yield 'event: one';

            ++$pulled;

            yield 'event: two';
        })());

        self::assertSame('event: one', $stream->read(1_024));
        self::assertSame(1, $pulled);
        self::assertSame('event: two', $stream->read(1_024));
        self::assertSame(2, $pulled);
        self::assertTrue($stream->eof());
    }

    public function testStringCastingConsumesTheRemainingForwardOnlyContents(): void
    {
        $stream = new IterableStream([
            'ab',
            'cd',
        ]);

        self::assertSame('a', $stream->read(1));
        self::assertSame('bcd', (string) $stream);
        self::assertSame(4, $stream->tell());
    }

    public function testRejectsNonStringChunksWhenTheyAreRead(): void
    {
        $stream = new IterableStream([
            'valid',
            42,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('must be strings');

        $stream->getContents();
    }

    /**
     * Generator execution is deferred until the stream advances it.
     *
     * @noinspection PhpUnreachableStatementInspection
     */
    public function testWrapsIteratorFailuresAsStreamReadFailures(): void
    {
        $failure = new LogicException('source failed');
        $stream = new IterableStream(self::failingChunks($failure));

        try {
            $stream->getContents();
            self::fail('The iterator failure was not propagated.');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception->getPrevious());
        }
    }

    public function testRejectsSeeking(): void
    {
        $stream = new IterableStream([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('not seekable');

        $stream->seek(0);
    }

    public function testRejectsWriting(): void
    {
        $stream = new IterableStream([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('not writable');

        $stream->write('payload');
    }

    public function testCloseReleasesTheSourceAndDisablesCapabilities(): void
    {
        $stream = new IterableStream([
            'payload',
        ]);
        $stream->close();

        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertTrue($stream->eof());
        self::assertNull($stream->getSize());
        self::assertSame([], $stream->getMetadata());
        self::assertNull($stream->detach());
        self::assertSame('', (string) $stream);

        $this->expectException(RuntimeException::class);
        $stream->read(1);
    }
    /** @return Generator<int, string> */
    private static function failingChunks(LogicException $failure): Generator
    {
        yield 'valid';

        throw $failure;
    }

}
