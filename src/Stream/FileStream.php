<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

use function fopen;
use function sprintf;

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

        $this->initializeResource($resource);
    }
}
