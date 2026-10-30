<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use RuntimeException;
use SuperKernel\HttpMessage\Stream\Resource\ResourceContents;
use SuperKernel\HttpMessage\Stream\Resource\ResourceCursor;
use SuperKernel\HttpMessage\Stream\Resource\ResourceGuard;
use Throwable;

use function fclose;
use function feof;
use function fread;
use function fstat;
use function ftell;
use function fwrite;
use function is_array;
use function is_int;
use function is_resource;
use function stream_get_meta_data;
use function substr;

use const SEEK_SET;

/**
 * Native handle lifecycle shared by the resource-backed PSR streams.
 *
 * @internal
 */
trait ResourceStreamBehavior
{
    /** @var resource|null */
    private mixed $resource = null;

    private ?int $size = null;

    private bool $seekable;

    private bool $readable;

    private bool $writable;

    public function write(string $string): int
    {
        if (!is_resource($this->resource) || !$this->writable) {
            throw new RuntimeException('The HTTP message stream is not writable.');
        }

        if ('' === $string) {
            return 0;
        }

        $remaining = $string;
        $written = 0;

        while ('' !== $remaining) {
            $count = @fwrite(ResourceGuard::requireAttached($this->resource), $remaining);

            if (false === $count || 0 === $count) {
                throw new RuntimeException('Unable to write to the HTTP message stream.');
            }

            $written += $count;
            $remaining = substr($remaining, $count);
        }

        $this->size = null;

        return $written;
    }

    public function isWritable(): bool
    {
        return is_resource($this->resource) && $this->writable;
    }

    public function rewind(): void
    {
        ResourceCursor::seek($this->resource, $this->seekable, 0, SEEK_SET);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        ResourceCursor::seek($this->resource, $this->seekable, $offset, $whence);
    }

    public function isSeekable(): bool
    {
        return is_resource($this->resource) && $this->seekable;
    }

    public function __toString(): string
    {
        if (!is_resource($this->resource)) {
            return '';
        }

        try {
            if ($this->seekable) {
                ResourceCursor::seek($this->resource, $this->seekable, 0, SEEK_SET);
            }

            return ResourceContents::remaining($this->resource, $this->readable);
        } catch (Throwable) {
            return '';
        }
    }

    public function getContents(): string
    {
        return ResourceContents::remaining($this->resource, $this->readable);
    }

    public function isReadable(): bool
    {
        return is_resource($this->resource) && $this->readable;
    }

    public function getSize(): ?int
    {
        if (null !== $this->size) {
            return $this->size;
        }

        if (!is_resource($this->resource)) {
            return null;
        }

        $statistics = @fstat($this->resource);

        if (!is_array($statistics) || !is_int($statistics['size'] ?? null)) {
            return null;
        }

        return $this->size = $statistics['size'];
    }

    public function tell(): int
    {
        $resource = ResourceGuard::requireAttached($this->resource);
        $position = @ftell($resource);

        if (false === $position) {
            throw new RuntimeException('Unable to determine the HTTP message stream position.');
        }

        return $position;
    }

    public function eof(): bool
    {
        return !is_resource($this->resource) || feof($this->resource);
    }

    public function read(int $length): string
    {
        if (0 > $length) {
            throw new RuntimeException('HTTP message stream read length cannot be negative.');
        }

        if (!is_resource($this->resource) || !$this->readable) {
            throw new RuntimeException('The HTTP message stream is not readable.');
        }

        if (0 === $length) {
            return '';
        }

        $contents = @fread(ResourceGuard::requireAttached($this->resource), $length);

        if (false === $contents) {
            throw new RuntimeException('Unable to read from the HTTP message stream.');
        }

        return $contents;
    }

    public function getMetadata(?string $key = null): mixed
    {
        if (!is_resource($this->resource)) {
            return null === $key ? [] : null;
        }

        $metadata = stream_get_meta_data($this->resource);

        return null === $key ? $metadata : $metadata[$key] ?? null;
    }

    public function __destruct()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->size = null;

        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    public function close(): void
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->size = null;

        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->size = null;

        return is_resource($resource) ? $resource : null;
    }
}
