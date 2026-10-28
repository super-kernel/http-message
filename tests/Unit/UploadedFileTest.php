<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use Error;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SuperKernel\HttpMessage\Stream\StringStream;
use SuperKernel\HttpMessage\UploadedFile;
use Throwable;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

final class UploadedFileTest extends TestCase
{
    public function testMovesAStreamAndRetainsMetadata(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'super-kernel-http-message-');
        self::assertNotFalse($target);
        unlink($target);

        $upload = new UploadedFile(
            new StringStream('payload'),
            clientFilename: 'payload.txt',
            clientMediaType: 'text/plain',
        );

        try {
            self::assertSame(7, $upload->getSize());
            self::assertSame(UPLOAD_ERR_OK, $upload->getError());
            self::assertSame('payload.txt', $upload->getClientFilename());
            self::assertSame('text/plain', $upload->getClientMediaType());

            $upload->moveTo($target);

            self::assertSame('payload', file_get_contents($target));
            self::assertSame(7, $upload->getSize());

            $this->expectException(RuntimeException::class);
            $upload->getStream();
        } finally {
            if (file_exists($target)) {
                unlink($target);
            }
        }
    }

    public function testMovesAnEmptyStream(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'super-kernel-http-message-');
        self::assertNotFalse($target);
        unlink($target);

        try {
            new UploadedFile(new StringStream())->moveTo($target);

            self::assertSame('', file_get_contents($target));
        } finally {
            if (file_exists($target)) {
                unlink($target);
            }
        }
    }

    #[DataProvider('moveFailures')]
    public function testNormalizesMoveFailuresAndPreservesTheSource(string $method, Throwable $failure): void
    {
        $target = tempnam(sys_get_temp_dir(), 'super-kernel-http-message-');
        self::assertNotFalse($target);
        unlink($target);

        $source = $this->createMock(StreamInterface::class);
        $source->expects(self::once())->method($method)->willThrowException($failure);
        $source->expects(self::never())->method('close');

        if ('read' === $method) {
            $source->method('eof')->willReturn(false);
        }

        $upload = new UploadedFile($source);

        try {
            $upload->moveTo($target);
            self::fail('The failed upload move did not throw.');
        } catch (RuntimeException $exception) {
            if ($failure instanceof RuntimeException) {
                self::assertSame($failure, $exception);
            } else {
                self::assertSame($failure, $exception->getPrevious());
                self::assertStringContainsString($target, $exception->getMessage());
            }

            self::assertFileDoesNotExist($target);
            self::assertSame($source, $upload->getStream());
        } finally {
            if (file_exists($target)) {
                unlink($target);
            }
        }
    }

    /**
     * @return array<string, array{string, Throwable}>
     */
    public static function moveFailures(): array
    {
        return [
            'runtime read failure' => [
                'read',
                new RuntimeException('Unable to read the source.'),
            ],
            'unexpected read exception' => [
                'read',
                new LogicException('Unexpected source failure.'),
            ],
            'read error' => [
                'read',
                new Error('Source read failed.'),
            ],
            'size error' => [
                'getSize',
                new Error('Source size lookup failed.'),
            ],
        ];
    }

    public function testMovesATemporaryFilePath(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'super-kernel-http-message-source-');
        $target = tempnam(sys_get_temp_dir(), 'super-kernel-http-message-target-');
        self::assertNotFalse($source);
        self::assertNotFalse($target);
        self::assertSame(7, file_put_contents($source, 'payload'));
        unlink($target);

        try {
            $upload = new UploadedFile($source);
            $upload->moveTo($target);

            self::assertFileDoesNotExist($source);
            self::assertSame('payload', file_get_contents($target));
            self::assertSame(7, $upload->getSize());
        } finally {
            if (file_exists($source)) {
                unlink($source);
            }

            if (file_exists($target)) {
                unlink($target);
            }
        }
    }

    public function testRejectsAccessWhenTheUploadFailed(): void
    {
        $upload = new UploadedFile(null, error: UPLOAD_ERR_NO_FILE);

        self::assertSame(UPLOAD_ERR_NO_FILE, $upload->getError());
        self::assertNull($upload->getSize());

        $this->expectException(RuntimeException::class);
        $upload->getStream();
    }
}
