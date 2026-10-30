<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Upload;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;

use function is_array;
use function sprintf;

/**
 * Validates normalized uploaded-file trees of server requests.
 *
 * @internal
 */
final class UploadedFileTree
{
    /**
     * @param array<array-key, UploadedFileInterface|array> $uploadedFiles
     * @param string                                        $path          The tree path reported in failures
     *
     * @throws InvalidArgumentException When a leaf is neither an uploaded file nor a nested array
     */
    public static function validate(array $uploadedFiles, string $path = 'uploadedFiles'): void
    {
        foreach ($uploadedFiles as $name => $uploadedFile) {
            $entryPath = $path . '[' . $name . ']';

            if ($uploadedFile instanceof UploadedFileInterface) {
                continue;
            }

            if (is_array($uploadedFile)) {
                self::validate($uploadedFile, $entryPath);

                continue;
            }

            throw new InvalidArgumentException(sprintf(
                '%s must contain only uploaded files or nested arrays.',
                $entryPath,
            ));
        }
    }
}
