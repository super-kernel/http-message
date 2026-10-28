<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use ArrayIterator;
use Iterator;
use IteratorIterator;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

use function get_debug_type;
use function implode;
use function is_array;
use function is_string;
use function sprintf;
use function strlen;
use function substr;

use const SEEK_SET;

final class IterableStream implements StreamInterface
{
    private string $buffer = '';

    private bool $closed = false;

    private bool $started = false;

    private ?Iterator $iterator;

    private bool $sourceEnded = false;

    private int $position = 0;

    /**
     * @param iterable $chunks Each yielded value must be a string
     */
    public function __construct(iterable $chunks)
    {
        if (is_array($chunks)) {
            $this->iterator = new ArrayIterator($chunks);

            return;
        }

        $this->iterator = $chunks instanceof Iterator ? $chunks : new IteratorIterator($chunks);
    }

    public function __toString(): string
    {
        if ($this->closed) {
            return '';
        }

        try {
            return $this->getContents();
        } catch (Throwable) {
            return '';
        }
    }

    public function getContents(): string
    {
        $this->assertOpen();
        $chunks = [];

        while (!$this->eof()) {
            $chunks[] = $this->read(8_192);
        }

        return implode('', $chunks);
    }

    private function assertOpen(): void
    {
        if ($this->closed) {
            throw new RuntimeException('The iterable stream is detached or closed.');
        }
    }

    public function eof(): bool
    {
        if ($this->closed) {
            return true;
        }

        if ('' !== $this->buffer) {
            return false;
        }

        $this->fill();

        return $this->sourceEnded && '' === $this->buffer;
    }

    /**
     * Pulls source chunks only while nothing is buffered, so a read returns as soon as the source produced bytes.
     */
    private function fill(): void
    {
        while (!$this->sourceEnded && '' === $this->buffer) {
            try {
                $chunk = $this->nextChunk();
            } catch (RuntimeException $exception) {
                throw $exception;
            } catch (Throwable $throwable) {
                throw new RuntimeException('Unable to read from the iterable stream source.', previous: $throwable);
            }

            if (null === $chunk) {
                $this->sourceEnded = true;

                continue;
            }

            $this->buffer .= $chunk;
        }
    }

    private function nextChunk(): ?string
    {
        $iterator = $this->requireIterator();

        while (true) {
            if ($this->started) {
                $iterator->next();
            } else {
                $iterator->rewind();
                $this->started = true;
            }

            if (!$iterator->valid()) {
                return null;
            }

            $chunk = $iterator->current();

            if (!is_string($chunk)) {
                throw new RuntimeException(sprintf(
                    'Iterable stream chunks must be strings; got %s.',
                    get_debug_type($chunk),
                ));
            }

            if ('' !== $chunk) {
                return $chunk;
            }
        }
    }

    private function requireIterator(): Iterator
    {
        if (null === $this->iterator) {
            throw new RuntimeException('The iterable stream source is detached or closed.');
        }

        return $this->iterator;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->assertOpen();

        throw new RuntimeException('The iterable stream is not seekable.');
    }

    public function read(int $length): string
    {
        if (0 > $length) {
            throw new RuntimeException('Iterable stream read length cannot be negative.');
        }

        $this->assertOpen();

        if (0 === $length) {
            return '';
        }

        $this->fill();
        $contents = substr($this->buffer, 0, $length);
        $consumed = strlen($contents);
        $this->buffer = substr($this->buffer, $consumed);
        $this->position += $consumed;

        return $contents;
    }

    public function detach(): null
    {
        $this->close();

        return null;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->sourceEnded = true;
        $this->buffer = '';
        $this->iterator = null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        $this->assertOpen();

        return $this->position;
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
        $this->assertOpen();

        throw new RuntimeException('The iterable stream is not writable.');
    }

    public function isReadable(): bool
    {
        return !$this->closed;
    }

    public function getMetadata(?string $key = null): ?array
    {
        return null === $key ? [] : null;
    }

    public function __destruct()
    {
        $this->close();
    }
}
