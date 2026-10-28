<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

use const SEEK_SET;

final class ReadOnlyStream implements StreamInterface
{
    private ?StreamInterface $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function __toString(): string
    {
        if (null === $this->stream) {
            return '';
        }

        try {
            return (string)$this->stream;
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;

        return $stream?->detach();
    }

    public function getSize(): ?int
    {
        return $this->stream?->getSize();
    }

    public function tell(): int
    {
        return $this->requireStream()->tell();
    }

    private function requireStream(): StreamInterface
    {
        if (null === $this->stream) {
            throw new RuntimeException('The read-only stream is detached or closed.');
        }

        return $this->stream;
    }

    public function eof(): bool
    {
        return null === $this->stream || $this->stream->eof();
    }

    public function isSeekable(): bool
    {
        return null !== $this->stream && $this->stream->isSeekable();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->requireStream()->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->requireStream()->rewind();
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        $this->requireStream();

        throw new RuntimeException('The read-only stream is not writable.');
    }

    public function isReadable(): bool
    {
        return null !== $this->stream && $this->stream->isReadable();
    }

    public function read(int $length): string
    {
        return $this->requireStream()->read($length);
    }

    public function getContents(): string
    {
        return $this->requireStream()->getContents();
    }

    public function getMetadata(?string $key = null): mixed
    {
        if (null === $this->stream) {
            return null === $key ? [] : null;
        }

        return $this->stream->getMetadata($key);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        $stream = $this->stream;
        $this->stream = null;
        $stream?->close();
    }
}
