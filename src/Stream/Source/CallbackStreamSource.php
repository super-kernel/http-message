<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream\Source;

use Closure;
use RuntimeException;
use Throwable;

use function get_debug_type;
use function is_string;
use function sprintf;
use function strlen;
use function substr;

/**
 * Pulls, validates, and buffers callback reader chunks and tracks the callback stream position and closed state.
 *
 * @internal
 */
final class CallbackStreamSource
{
    private string $buffer = '';

    private bool $closed = false;

    /** @var (Closure(positive-int): (non-empty-string|null))|null */
    private ?Closure $reader;

    private bool $sourceEnded = false;

    private int $position = 0;

    /**
     * @param Closure(positive-int): (non-empty-string|null) $reader
     */
    public function __construct(Closure $reader)
    {
        $this->reader = $reader;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @throws RuntimeException When the callback stream is detached or closed
     */
    public function assertOpen(): void
    {
        if ($this->closed) {
            throw new RuntimeException('The callback stream is detached or closed.');
        }
    }

    /**
     * Asks the reader for chunks only while nothing is buffered, so a read returns as soon as the source produced
     * bytes; the reader receives the full requested length.
     *
     * @throws RuntimeException When the source is released, returns an invalid chunk, or fails, with the failure as
     *                          the previous exception
     */
    public function fill(int $length): void
    {
        while (!$this->sourceEnded && '' === $this->buffer) {
            try {
                $reader = $this->reader;

                if (null === $reader) {
                    throw new RuntimeException('The callback stream source is detached or closed.');
                }

                $chunk = $reader($length);
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
        $this->reader = null;
    }
}
