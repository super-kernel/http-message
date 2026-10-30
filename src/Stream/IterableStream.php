<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use ArrayIterator;
use Iterator;
use IteratorIterator;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\Source\IterableStreamContents;
use SuperKernel\HttpMessage\Stream\Source\IterableStreamSource;
use Throwable;

use function is_array;

use const SEEK_SET;

final class IterableStream implements StreamInterface
{
    private IterableStreamSource $source;

    /**
     * @param iterable $chunks Each yielded value must be a string
     */
    public function __construct(iterable $chunks)
    {
        if (is_array($chunks)) {
            $this->source = new IterableStreamSource(new ArrayIterator($chunks));

            return;
        }

        $this->source = new IterableStreamSource(
            $chunks instanceof Iterator ? $chunks : new IteratorIterator($chunks),
        );
    }

    /**
     * Keeps the position, buffered bytes, started state, and closed state of a clone independent while both streams
     * share the iterator.
     */
    public function __clone(): void
    {
        $this->source = clone $this->source;
    }

    public function __toString(): string
    {
        if ($this->source->isClosed()) {
            return '';
        }

        try {
            return IterableStreamContents::remaining($this->source);
        } catch (Throwable) {
            return '';
        }
    }

    public function getContents(): string
    {
        $this->source->assertOpen();

        return IterableStreamContents::remaining($this->source);
    }

    public function eof(): bool
    {
        if ($this->source->isClosed()) {
            return true;
        }

        $this->source->fill();

        return $this->source->isExhausted();
    }

    public function rewind(): void
    {
        $this->source->assertOpen();

        throw new RuntimeException('The iterable stream is not seekable.');
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->source->assertOpen();

        throw new RuntimeException('The iterable stream is not seekable.');
    }

    public function read(int $length): string
    {
        if (0 > $length) {
            throw new RuntimeException('Iterable stream read length cannot be negative.');
        }

        $this->source->assertOpen();

        if (0 === $length) {
            return '';
        }

        $this->source->fill();

        return $this->source->take($length);
    }

    public function detach(): null
    {
        $this->source->close();

        return null;
    }

    public function close(): void
    {
        $this->source->close();
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        $this->source->assertOpen();

        return $this->source->position();
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        $this->source->assertOpen();

        throw new RuntimeException('The iterable stream is not writable.');
    }

    public function isReadable(): bool
    {
        return !$this->source->isClosed();
    }

    public function getMetadata(?string $key = null): ?array
    {
        return null === $key ? [] : null;
    }

    public function __destruct()
    {
        $this->source->close();
    }
}
