<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\FileStream;
use SuperKernel\HttpMessage\Upload\UploadedFileCopier;
use SuperKernel\HttpMessage\Upload\UploadedFileGuard;
use SuperKernel\HttpMessage\Upload\UploadedFileMover;
use SuperKernel\HttpMessage\Upload\UploadedFileSize;
use Throwable;

use function in_array;
use function is_string;
use function sprintf;
use function str_contains;

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
        UploadedFileGuard::requireAvailable($this->moved, $this->error);

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

        UploadedFileGuard::requireAvailable($this->moved, $this->error);

        try {
            if (null === $this->size) {
                $this->size = UploadedFileSize::detect($this->stream, $this->file);
            }

            $sourcePath = $this->file;

            if (null !== $sourcePath) {
                $this->stream?->close();
                $this->stream = null;
                UploadedFileMover::move($sourcePath, $targetPath);

                $this->moved = true;
                $this->stream = null;
                $this->file = null;

                return;
            }

            // The source stream may have moved this upload re-entrantly while reporting its size.
            UploadedFileGuard::requireAvailable($this->moved, $this->error);
            $source = $this->stream;

            if (null === $source) {
                throw new RuntimeException('No stream is available for the uploaded file.');
            }

            UploadedFileCopier::copy($source, $targetPath);
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

        return $this->size = UploadedFileSize::detect($this->stream, $this->file);
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
}
