<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Upload;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

use function fclose;
use function fopen;
use function fwrite;
use function sprintf;
use function substr;
use function unlink;

/**
 * Copies a PSR stream into an uploaded-file target path and removes a partially written target on failure.
 *
 * @internal
 */
final class UploadedFileCopier
{
    /**
     * @throws Throwable When opening the target, reading the source, or writing a chunk fails
     */
    public static function copy(StreamInterface $source, string $targetPath): void
    {
        $target = @fopen($targetPath, 'wb');

        if (false === $target) {
            throw new RuntimeException(sprintf('Unable to open uploaded file target %s.', $targetPath));
        }

        try {
            if ($source->isSeekable()) {
                $source->rewind();
            }

            while (!$source->eof()) {
                $chunk = $source->read(8_192);

                if ('' === $chunk) {
                    if ($source->eof()) {
                        break;
                    }

                    throw new RuntimeException('Unable to make progress while reading the uploaded file stream.');
                }

                $remaining = $chunk;

                while ('' !== $remaining) {
                    $written = @fwrite($target, $remaining);

                    if (false === $written || 0 === $written) {
                        throw new RuntimeException(sprintf(
                            'Unable to write uploaded file target %s.',
                            $targetPath,
                        ));
                    }

                    $remaining = substr($remaining, $written);
                }
            }
        } catch (Throwable $throwable) {
            fclose($target);
            @unlink($targetPath);

            throw $throwable;
        }

        fclose($target);
    }
}
