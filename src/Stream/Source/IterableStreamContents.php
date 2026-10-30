<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream\Source;

use RuntimeException;

use function implode;

/**
 * Drains the remaining contents of an iterable stream source in bounded chunks.
 *
 * @internal
 */
final class IterableStreamContents
{
    /**
     * @throws RuntimeException When the source fails or yields a non-string chunk, or the stream is closed while a
     *                          produced chunk is pending
     */
    public static function remaining(IterableStreamSource $source): string
    {
        $chunks = [];

        while (!$source->isClosed()) {
            $source->fill();

            if ($source->isExhausted()) {
                break;
            }

            // The source may close the stream while it produces a chunk.
            $source->assertOpen();
            $chunks[] = $source->take(8_192);
        }

        return implode('', $chunks);
    }
}
