<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use SuperKernel\HttpMessage\ServerRequest;
use SuperKernel\HttpMessage\Stream\StringStream;
use SuperKernel\HttpMessage\UploadedFile;

final class ServerRequestTest extends TestCase
{
    /**
     * These assertions protect the public interface contract even when static types already imply it.
     *
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testRetainsServerDataAndChangesApplicationDataImmutably(): void
    {
        $upload = new UploadedFile(new StringStream('file'));
        $request = new ServerRequest(
            'POST',
            '/submit',
            serverParams: [
                'REMOTE_ADDR' => '127.0.0.1',
            ],
            cookieParams: [
                'session' => 'one',
            ],
            queryParams: [
                'page' => 1,
            ],
            uploadedFiles: [
                'document' => $upload,
            ],
            parsedBody: [
                'name' => 'example',
            ],
            attributes: [
                'nullable' => null,
            ],
        );

        $changed = $request
            ->withCookieParams([
                'session' => 'two',
            ])
            ->withQueryParams([
                'page' => 2,
            ])
            ->withAttribute('route', 'submit')
            ->withParsedBody((object) [
                'name' => 'changed',
            ]);

        self::assertInstanceOf(ServerRequestInterface::class, $request);
        self::assertSame('127.0.0.1', $request->getServerParams()['REMOTE_ADDR']);
        self::assertSame('one', $request->getCookieParams()['session']);
        self::assertSame(1, $request->getQueryParams()['page']);
        self::assertSame($upload, $request->getUploadedFiles()['document']);
        self::assertNull($request->getAttribute('nullable', 'fallback'));
        self::assertSame('fallback', $request->getAttribute('missing', 'fallback'));
        self::assertSame('two', $changed->getCookieParams()['session']);
        self::assertSame(2, $changed->getQueryParams()['page']);
        self::assertSame('submit', $changed->getAttribute('route'));
        self::assertSame('changed', $changed->getParsedBody()->name);
        self::assertFalse(isset($request->getAttributes()['route']));
    }

    public function testValidatesUploadedFilesAndParsedBodies(): void
    {
        $request = new ServerRequest('POST', '/');

        try {
            $request->withUploadedFiles([
                'invalid',
            ]);
            self::fail('The invalid uploaded file tree was accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        $request->withParsedBody('invalid');
    }
}
