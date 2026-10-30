<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Stream\Resource;

use function in_array;
use function is_string;
use function str_contains;

/**
 * Derives PSR stream capabilities from native stream metadata.
 *
 * @internal
 */
final class ResourceCapabilities
{
    /**
     * @param array<string, mixed> $metadata The result of stream_get_meta_data()
     */
    public static function isSeekable(array $metadata): bool
    {
        return true === ($metadata['seekable'] ?? false);
    }

    /**
     * @param array<string, mixed> $metadata The result of stream_get_meta_data()
     */
    public static function isReadable(array $metadata): bool
    {
        $mode = is_string($metadata['mode'] ?? null) ? $metadata['mode'] : '';
        $firstMode = '' === $mode ? '' : $mode[0];

        return 'r' === $firstMode || str_contains($mode, '+');
    }

    /**
     * @param array<string, mixed> $metadata The result of stream_get_meta_data()
     */
    public static function isWritable(array $metadata): bool
    {
        $mode = is_string($metadata['mode'] ?? null) ? $metadata['mode'] : '';
        $firstMode = '' === $mode ? '' : $mode[0];

        return in_array($firstMode, [
                'w',
                'a',
                'x',
                'c',
            ], true) || str_contains($mode, '+');
    }
}
