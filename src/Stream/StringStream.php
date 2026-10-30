<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\Resource\ResourceCapabilities;
use SuperKernel\HttpMessage\Stream\Resource\ResourceCursor;

use function fclose;
use function fopen;
use function fwrite;
use function stream_get_meta_data;
use function substr;

use const SEEK_SET;

final class StringStream implements StreamInterface
{
    use ResourceStreamBehavior;

    /**
     * @throws RuntimeException When the temporary stream cannot be opened or initialized
     */
    public function __construct(string $contents = '')
    {
        $resource = @fopen('php://temp', 'r+b');

        if (false === $resource) {
            throw new RuntimeException('Unable to open a temporary HTTP message stream.');
        }

        $metadata = stream_get_meta_data($resource);
        $this->resource = $resource;
        $this->seekable = ResourceCapabilities::isSeekable($metadata);
        $this->readable = ResourceCapabilities::isReadable($metadata);
        $this->writable = ResourceCapabilities::isWritable($metadata);

        try {
            if ('' !== $contents) {
                $remaining = $contents;

                while ('' !== $remaining) {
                    $count = @fwrite($resource, $remaining);

                    if (false === $count || 0 === $count) {
                        throw new RuntimeException('Unable to write to the HTTP message stream.');
                    }

                    $remaining = substr($remaining, $count);
                }

                ResourceCursor::seek($resource, $this->seekable, 0, SEEK_SET);
            }
        } catch (RuntimeException $exception) {
            $this->resource = null;
            fclose($resource);

            throw $exception;
        }
    }
}
