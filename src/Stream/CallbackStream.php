<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\Source\CallbackStreamContents;
use SuperKernel\HttpMessage\Stream\Source\CallbackStreamSource;
use Throwable;

use const SEEK_SET;

final class CallbackStream implements StreamInterface
{
    private CallbackStreamSource $source;

    /**
     * @param callable(positive-int): (non-empty-string|null) $reader
     */
    public function __construct(callable $reader)
    {
        $this->source = new CallbackStreamSource($reader(...));
    }

    /**
     * Keeps the position, buffered bytes, and closed state of a clone independent while both streams share the reader.
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
            return CallbackStreamContents::remaining($this->source);
        } catch (Throwable) {
            return '';
        }
    }

    public function getContents(): string
    {
        $this->source->assertOpen();

        return CallbackStreamContents::remaining($this->source);
    }

    public function eof(): bool
    {
        if ($this->source->isClosed()) {
            return true;
        }

        $this->source->fill(8_192);

        return $this->source->isExhausted();
    }

    public function read(int $length): string
    {
        if (0 > $length) {
            throw new RuntimeException('Callback stream read length cannot be negative.');
        }

        $this->source->assertOpen();

        if (0 === $length) {
            return '';
        }

        $this->source->fill($length);

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

    public function rewind(): void
    {
        $this->source->assertOpen();

        throw new RuntimeException('The callback stream is not seekable.');
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->source->assertOpen();

        throw new RuntimeException('The callback stream is not seekable.');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        $this->source->assertOpen();

        throw new RuntimeException('The callback stream is not writable.');
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
