<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\FileStream;
use Throwable;

use function fclose;
use function filesize;
use function fopen;
use function fwrite;
use function in_array;
use function is_int;
use function is_string;
use function is_uploaded_file;
use function move_uploaded_file;
use function rename;
use function sprintf;
use function str_contains;
use function substr;
use function unlink;

use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_OK;
use const UPLOAD_ERR_PARTIAL;

final class UploadedFile implements UploadedFileInterface
{
    private ?StreamInterface $stream;

    private ?string $file;

    private ?int $size;

    private bool $moved = false;

    /**
     * @throws InvalidArgumentException When the error code, size, or successful-upload source is invalid
     */
    public function __construct(
        StreamInterface|string|null $streamOrFile,
        ?int                        $size = null,
        private readonly int        $error = UPLOAD_ERR_OK,
        private readonly ?string    $clientFilename = null,
        private readonly ?string    $clientMediaType = null,
    )
    {
        if (!in_array($error, [
            UPLOAD_ERR_OK,
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE,
            UPLOAD_ERR_PARTIAL,
            UPLOAD_ERR_NO_FILE,
            UPLOAD_ERR_NO_TMP_DIR,
            UPLOAD_ERR_CANT_WRITE,
            UPLOAD_ERR_EXTENSION,
        ], true)) {
            throw new InvalidArgumentException(sprintf('Invalid upload error code %d.', $error));
        }

        if (null !== $size && 0 > $size) {
            throw new InvalidArgumentException('Uploaded file size cannot be negative.');
        }

        if (UPLOAD_ERR_OK === $error && (null === $streamOrFile || '' === $streamOrFile)) {
            throw new InvalidArgumentException('A successful upload requires a stream or temporary file path.');
        }

        $this->stream = $streamOrFile instanceof StreamInterface ? $streamOrFile : null;
        $this->file = is_string($streamOrFile) && '' !== $streamOrFile ? $streamOrFile : null;
        $this->size = $size;
    }

    public function getStream(): StreamInterface
    {
        $this->assertAvailable();

        if (null !== $this->stream) {
            return $this->stream;
        }

        if (null === $this->file) {
            throw new RuntimeException('No stream is available for the uploaded file.');
        }

        return $this->stream = new FileStream($this->file);
    }

    public function moveTo(string $targetPath): void
    {
        if ('' === $targetPath || str_contains($targetPath, "\0")) {
            throw new InvalidArgumentException('The uploaded file target path is invalid.');
        }

        $this->assertAvailable();

        try {
            if (null === $this->size) {
                $this->size = $this->getSize();
            }

            if (null !== $this->file && $this->moveFile($targetPath)) {
                $this->moved = true;
                $this->stream = null;
                $this->file = null;

                return;
            }

            $source = $this->getStream();
            $this->copyStream($source, $targetPath);
            $source->close();

            $this->moved = true;
            $this->stream = null;
            $this->file = null;
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw new RuntimeException(
                sprintf('Unable to move the uploaded file to %s.', $targetPath),
                previous: $throwable,
            );
        }
    }

    public function getSize(): ?int
    {
        if (null !== $this->size) {
            return $this->size;
        }

        if (null !== $this->stream) {
            return $this->size = $this->stream->getSize();
        }

        if (null === $this->file) {
            return null;
        }

        $size = @filesize($this->file);

        return $this->size = is_int($size) ? $size : null;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    private function assertAvailable(): void
    {
        if ($this->moved) {
            throw new RuntimeException('The uploaded file has already been moved.');
        }

        if (UPLOAD_ERR_OK !== $this->error) {
            throw new RuntimeException(sprintf(
                'The uploaded file is unavailable because upload error %d occurred.',
                $this->error,
            ));
        }
    }

    /**
     * @throws Throwable When moving, copying, or removing the temporary source fails
     */
    private function moveFile(string $targetPath): bool
    {
        $sourcePath = $this->file;

        if (null === $sourcePath) {
            return false;
        }

        $this->stream?->close();
        $this->stream = null;

        $moved = is_uploaded_file($sourcePath)
            ? @move_uploaded_file($sourcePath, $targetPath)
            : @rename($sourcePath, $targetPath);

        if ($moved) {
            return true;
        }

        $source = new FileStream($sourcePath);

        try {
            $this->copyStream($source, $targetPath);
        } catch (Throwable $throwable) {
            $source->close();
            @unlink($targetPath);

            throw $throwable;
        }

        $source->close();

        if (!@unlink($sourcePath)) {
            @unlink($targetPath);

            throw new RuntimeException(sprintf(
                'Unable to remove uploaded file source %s after copying.',
                $sourcePath,
            ));
        }

        return true;
    }

    /**
     * @throws Throwable When opening the target, reading the source, or writing a chunk fails
     */
    private function copyStream(StreamInterface $source, string $targetPath): void
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

                self::writeChunk($target, $chunk, $targetPath);
            }
        } catch (Throwable $throwable) {
            fclose($target);
            @unlink($targetPath);

            throw $throwable;
        }

        fclose($target);
    }

    /**
     * @param resource $target
     */
    private static function writeChunk(mixed $target, string $chunk, string $targetPath): void
    {
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
}
