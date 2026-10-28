<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function fopen;

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

        $this->initializeResource($resource);

        try {
            if ('' !== $contents) {
                $this->write($contents);
                $this->rewind();
            }
        } catch (RuntimeException $exception) {
            $this->close();

            throw $exception;
        }
    }
}
