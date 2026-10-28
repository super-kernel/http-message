<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

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
        $this->initializeResource($resource);
    }
}
