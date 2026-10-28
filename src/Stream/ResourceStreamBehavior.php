<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

use function fclose;
use function feof;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function get_resource_type;
use function in_array;
use function is_array;
use function is_int;
use function is_resource;
use function is_string;
use function sprintf;
use function str_contains;
use function stream_get_contents;
use function stream_get_meta_data;
use function substr;

use const SEEK_CUR;
use const SEEK_END;
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

    /**
     * @param resource $resource
     */
    final protected function initializeResource(mixed $resource): void
    {
        if (!is_resource($resource) || 'stream' !== get_resource_type($resource)) {
            throw new InvalidArgumentException('A native stream resource is required.');
        }

        $metadata = stream_get_meta_data($resource);
        $mode = is_string($metadata['mode'] ?? null) ? $metadata['mode'] : '';
        $firstMode = '' === $mode ? '' : $mode[0];

        $this->resource = $resource;
        $this->seekable = true === ($metadata['seekable'] ?? false);
        $this->readable = 'r' === $firstMode || str_contains($mode, '+');
        $this->writable = in_array($firstMode, [
                'w',
                'a',
                'x',
                'c',
            ], true) || str_contains($mode, '+');
    }

    public function write(string $string): int
    {
        if (!$this->isWritable()) {
            throw new RuntimeException('The HTTP message stream is not writable.');
        }

        if ('' === $string) {
            return 0;
        }

        $remaining = $string;
        $written = 0;

        while ('' !== $remaining) {
            $count = @fwrite($this->requireResource(), $remaining);

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

    /**
     * @return resource
     */
    private function requireResource(): mixed
    {
        if (!is_resource($this->resource)) {
            throw new RuntimeException('The HTTP message stream is detached or closed.');
        }

        return $this->resource;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!in_array($whence, [
            SEEK_SET,
            SEEK_CUR,
            SEEK_END,
        ], true)) {
            throw new RuntimeException(sprintf(
                'Invalid HTTP message stream seek mode %d.',
                $whence,
            ));
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException('The HTTP message stream is not seekable.');
        }

        if (0 !== @fseek($this->requireResource(), $offset, $whence)) {
            throw new RuntimeException(sprintf(
                'Unable to seek the HTTP message stream to offset %d.',
                $offset,
            ));
        }
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
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (Throwable) {
            return '';
        }
    }

    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('The HTTP message stream is not readable.');
        }

        $contents = @stream_get_contents($this->requireResource());

        if (false === $contents) {
            throw new RuntimeException('Unable to read the remaining HTTP message stream contents.');
        }

        return $contents;
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
        $resource = $this->requireResource();
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

        if (!$this->isReadable()) {
            throw new RuntimeException('The HTTP message stream is not readable.');
        }

        if (0 === $length) {
            return '';
        }

        $contents = @fread($this->requireResource(), $length);

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
        $this->close();
    }

    public function close(): void
    {
        $resource = $this->detach();

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
