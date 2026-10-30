<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use SuperKernel\HttpMessage\Stream\Resource\ResourceCapabilities;

use function get_resource_type;
use function is_resource;
use function stream_get_meta_data;

final class ResourceStream implements StreamInterface
{
    use ResourceStreamBehavior;

    /**
     * @param resource $resource
     *
     * @throws InvalidArgumentException When the value is not a native stream resource
     */
    public function __construct(mixed $resource)
    {
        if (!is_resource($resource) || 'stream' !== get_resource_type($resource)) {
            throw new InvalidArgumentException('A native stream resource is required.');
        }

        $metadata = stream_get_meta_data($resource);
        $this->resource = $resource;
        $this->seekable = ResourceCapabilities::isSeekable($metadata);
        $this->readable = ResourceCapabilities::isReadable($metadata);
        $this->writable = ResourceCapabilities::isWritable($metadata);
    }
}
