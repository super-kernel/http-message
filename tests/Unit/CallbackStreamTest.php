<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\CallbackStream;

use function array_shift;

final class CallbackStreamTest extends TestCase
{
    public function testReturnsAvailableBytesAndBuffersOversizedChunks(): void
    {
        $chunks = [
            'abcdef',
            'gh',
            null,
        ];
        $requests = [];
        $stream = new CallbackStream(static function (int $length) use (&$chunks, &$requests): mixed {
            $requests[] = $length;

            return array_shift($chunks);
        });

        self::assertTrue($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertNull($stream->getSize());
        self::assertSame('abc', $stream->read(3));
        self::assertSame('de', $stream->read(2));
        self::assertSame(5, $stream->tell());
        self::assertSame('fgh', $stream->getContents());
        self::assertTrue($stream->eof());
        self::assertSame([
            3,
            8_192,
            8_192,
        ], $requests);
    }

    /**
     * The reader keeps the documented callback signature that receives the requested length.
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function testReadReturnsTheProducedChunkWithoutRequestingTheNextOne(): void
    {
        $calls = 0;
        $stream = new CallbackStream(static function (int $length) use (&$calls): ?string {
            ++$calls;

            return 1 === $calls ? 'event: one' : null;
        });

        self::assertSame('event: one', $stream->read(1_024));
        self::assertSame(1, $calls);
        self::assertSame('', $stream->read(1_024));
        self::assertSame(2, $calls);
        self::assertTrue($stream->eof());
    }

    public function testRejectsAnEmptyChunkBeforeEndOfSource(): void
    {
        $stream = new CallbackStream(static fn(int $length): string => '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('empty chunk');

        $stream->read(1);
    }

    public function testRejectsANonStringChunk(): void
    {
        $stream = new CallbackStream(static fn(int $length): int => 42);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('strings or null');

        $stream->read(1);
    }

    /**
     * The callback retains the signature required by its registration or reflection contract.
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function testWrapsCallbackFailuresAsStreamReadFailures(): void
    {
        $failure = new LogicException('source failed');
        $stream = new CallbackStream(
            static function (int $length) use ($failure): never {
                throw $failure;
            },
        );

        try {
            $stream->read(1);
            self::fail('The callback failure was not propagated.');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception->getPrevious());
        }
    }

    public function testRejectsSeeking(): void
    {
        $stream = new CallbackStream(static fn(): null => null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('not seekable');

        $stream->seek(0);
    }

    public function testRejectsWriting(): void
    {
        $stream = new CallbackStream(static fn(): null => null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('not writable');

        $stream->write('payload');
    }

    public function testCloseReleasesTheSourceAndDisablesCapabilities(): void
    {
        $stream = new CallbackStream(static fn(int $length): string => 'payload');
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
}
