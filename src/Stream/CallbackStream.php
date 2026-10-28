<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use Closure;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

use function get_debug_type;
use function implode;
use function is_string;
use function sprintf;
use function strlen;
use function substr;

use const SEEK_SET;

final class CallbackStream implements StreamInterface
{
    private string $buffer = '';

    private bool $closed = false;

    /** @var (Closure(positive-int): (non-empty-string|null))|null */
    private ?Closure $reader;

    private bool $sourceEnded = false;

    private int $position = 0;

    /**
     * @param callable(positive-int): (non-empty-string|null) $reader
     */
    public function __construct(callable $reader)
    {
        $this->reader = $reader(...);
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
            throw new RuntimeException('The callback stream is detached or closed.');
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

        $this->fill(8_192);

        return $this->sourceEnded && '' === $this->buffer;
    }

    /**
     * Asks the reader for chunks only while nothing is buffered, so a read returns as soon as the source produced
     * bytes; the reader receives the full requested length.
     */
    private function fill(int $length): void
    {
        while (!$this->sourceEnded && '' === $this->buffer) {
            try {
                $chunk = ($this->requireReader())($length);
            } catch (RuntimeException $exception) {
                throw $exception;
            } catch (Throwable $throwable) {
                throw new RuntimeException('Unable to read from the callback stream source.', previous: $throwable);
            }

            if (null === $chunk) {
                $this->sourceEnded = true;

                continue;
            }

            if (!is_string($chunk)) {
                throw new RuntimeException(sprintf(
                    'Callback stream sources must return strings or null; got %s.',
                    get_debug_type($chunk),
                ));
            }

            if ('' === $chunk) {
                throw new RuntimeException(
                    'Callback stream sources must not return an empty chunk before end of source.',
                );
            }

            $this->buffer .= $chunk;
        }
    }

    /**
     * @return Closure(positive-int): (non-empty-string|null)
     */
    private function requireReader(): Closure
    {
        if (null === $this->reader) {
            throw new RuntimeException('The callback stream source is detached or closed.');
        }

        return $this->reader;
    }

    public function read(int $length): string
    {
        if (0 > $length) {
            throw new RuntimeException('Callback stream read length cannot be negative.');
        }

        $this->assertOpen();

        if (0 === $length) {
            return '';
        }

        $this->fill($length);
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
        $this->reader = null;
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

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->assertOpen();

        throw new RuntimeException('The callback stream is not seekable.');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        $this->assertOpen();

        throw new RuntimeException('The callback stream is not writable.');
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
