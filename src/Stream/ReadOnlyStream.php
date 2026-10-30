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
        return ReadOnlyStreamGuard::requireAttached($this->stream)->tell();
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
        ReadOnlyStreamGuard::requireAttached($this->stream)->seek($offset, $whence);
    }

    public function rewind(): void
    {
        ReadOnlyStreamGuard::requireAttached($this->stream)->rewind();
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        ReadOnlyStreamGuard::requireAttached($this->stream);

        throw new RuntimeException('The read-only stream is not writable.');
    }

    public function isReadable(): bool
    {
        return null !== $this->stream && $this->stream->isReadable();
    }

    public function read(int $length): string
    {
        return ReadOnlyStreamGuard::requireAttached($this->stream)->read($length);
    }

    public function getContents(): string
    {
        return ReadOnlyStreamGuard::requireAttached($this->stream)->getContents();
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
        $stream = $this->stream;
        $this->stream = null;
        $stream?->close();
    }

    public function close(): void
    {
        $stream = $this->stream;
        $this->stream = null;
        $stream?->close();
    }
}
