<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\Resource\ResourceCapabilities;
use Throwable;

use function fopen;
use function sprintf;
use function stream_get_meta_data;

final class FileStream implements StreamInterface
{
    use ResourceStreamBehavior;

    /**
     * @throws RuntimeException When the file cannot be opened with the requested mode
     */
    public function __construct(string $filename, string $mode = 'rb')
    {
        try {
            $resource = @fopen($filename, $mode);
        } catch (Throwable $throwable) {
            throw new RuntimeException(
                sprintf('Unable to open HTTP message stream file %s.', $filename),
                previous: $throwable,
            );
        }

        if (false === $resource) {
            throw new RuntimeException(sprintf('Unable to open HTTP message stream file %s.', $filename));
        }

        $metadata = stream_get_meta_data($resource);
        $this->resource = $resource;
        $this->seekable = ResourceCapabilities::isSeekable($metadata);
        $this->readable = ResourceCapabilities::isReadable($metadata);
        $this->writable = ResourceCapabilities::isWritable($metadata);
    }
}
