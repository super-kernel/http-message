<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream\Source;

use RuntimeException;

use function implode;

/**
 * Drains the remaining contents of a callback stream source in reader-sized chunks.
 *
 * @internal
 */
final class CallbackStreamContents
{
    /**
     * @throws RuntimeException When the reader fails or returns an invalid chunk, or the stream is closed while a
     *                          produced chunk is pending
     */
    public static function remaining(CallbackStreamSource $source): string
    {
        $chunks = [];

        while (!$source->isClosed()) {
            $source->fill(8_192);

            if ($source->isExhausted()) {
                break;
            }

            // The reader may close the stream while it produces a chunk.
            $source->assertOpen();
            $chunks[] = $source->take(8_192);
        }

        return implode('', $chunks);
    }
}
