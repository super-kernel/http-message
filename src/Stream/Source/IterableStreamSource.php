<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream\Source;

use Iterator;
use RuntimeException;
use Throwable;

use function get_debug_type;
use function is_string;
use function sprintf;
use function strlen;
use function substr;

/**
 * Pulls, validates, and buffers iterable source chunks and tracks the iterable stream position and closed state.
 *
 * @internal
 */
final class IterableStreamSource
{
    private string $buffer = '';

    private bool $closed = false;

    private bool $started = false;

    private ?Iterator $iterator;

    private bool $sourceEnded = false;

    private int $position = 0;

    public function __construct(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @throws RuntimeException When the iterable stream is detached or closed
     */
    public function assertOpen(): void
    {
        if ($this->closed) {
            throw new RuntimeException('The iterable stream is detached or closed.');
        }
    }

    /**
     * Pulls source chunks only while nothing is buffered, so a read returns as soon as the source produced bytes.
     * Empty chunks are skipped; the iterator is rewound once before the first chunk.
     *
     * @throws RuntimeException When the source is released, yields a non-string chunk, or fails, with the failure as
     *                          the previous exception
     */
    public function fill(): void
    {
        while (!$this->sourceEnded && '' === $this->buffer) {
            $chunk = null;

            try {
                $iterator = $this->iterator;

                if (null === $iterator) {
                    throw new RuntimeException('The iterable stream source is detached or closed.');
                }

                if ($this->started) {
                    $iterator->next();
                } else {
                    $iterator->rewind();
                    $this->started = true;
                }

                while ($iterator->valid()) {
                    $current = $iterator->current();

                    if (!is_string($current)) {
                        throw new RuntimeException(sprintf(
                            'Iterable stream chunks must be strings; got %s.',
                            get_debug_type($current),
                        ));
                    }

                    if ('' !== $current) {
                        $chunk = $current;

                        break;
                    }

                    $iterator->next();
                }
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

    /**
     * Removes up to the requested number of buffered bytes and advances the stream position past them.
     */
    public function take(int $length): string
    {
        $contents = substr($this->buffer, 0, $length);
        $consumed = strlen($contents);
        $this->buffer = substr($this->buffer, $consumed);
        $this->position += $consumed;

        return $contents;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function isExhausted(): bool
    {
        return $this->sourceEnded && '' === $this->buffer;
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
}
