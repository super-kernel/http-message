<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SuperKernel HTTP Message

`super-kernel/http-message` provides the PSR-7 messages, URIs, streams, and uploaded files of SuperKernel, and gives services access to the request and response of the current scope.

## Installation

```bash
composer require super-kernel/http-message
```

The package has no requirement beyond PHP and provides `psr/http-message-implementation` 2.0. Its message objects work without a server and without a build; injecting the current request or response needs `super-kernel/di`, and something must store those messages, which `super-kernel/http-server` does for every request it handles.

## What it provides

- `Request`, `ServerRequest`, `Response`, and `Uri`, immutable PSR-7 objects with public constructors.
- Streams for a string, a native resource, a file, an iterable of chunks, a reader callback, and a read-only view of another stream.
- `UploadedFile` for a stream, a temporary file path, or a failed upload.
- `RequestContext` and `ResponseContext`, which store the messages of the current coroutine or process.
- `ServerRequestInterface` and `ResponseInterface` as container services that always act on the current messages.

It does not provide:

- `psr/http-factory` factories;
- routing, middleware, or controller invocation;
- conversion from native server requests or emission of responses;
- recovery from protocol errors;
- serialization of arrays or objects into a body.

## Quick start

Create a request and a response. Save the script as `bin/messages.php`:

```php
<?php

declare(strict_types=1);

use SuperKernel\HttpMessage\Request;
use SuperKernel\HttpMessage\Response;
use SuperKernel\HttpMessage\Stream\StringStream;

require dirname(__DIR__) . '/vendor/autoload.php';

$request = new Request('GET', 'https://Example.test:443/widgets?limit=10');
$json = $request->withHeader('Accept', 'application/json');

echo $request->getHeaderLine('Host'), ' ', $request->getRequestTarget(), PHP_EOL;
echo $request->hasHeader('Accept') ? 'changed' : 'unchanged', PHP_EOL;
echo $json->getHeaderLine('accept'), PHP_EOL;

$response = new Response(
    statusCode: 404,
    headers: [
        'Content-Type' => 'application/json',
    ],
    body: new StringStream('{"error":"not found"}'),
);

echo $response->getStatusCode(), ' ', $response->getReasonPhrase(), ' ', $response->getBody(), PHP_EOL;
```

Run the script:

```bash
php bin/messages.php
```

It prints:

```text
example.test /widgets?limit=10
unchanged
application/json
404 Not Found {"error":"not found"}
```

## Public surface

| Name | Kind | Purpose |
| --- | --- | --- |
| `SuperKernel\HttpMessage\Request` | Class | An outgoing request; derives the `Host` header from the URI when none is given. |
| `SuperKernel\HttpMessage\ServerRequest` | Class | An incoming request with server, cookie, query, upload, parsed-body, and attribute data. |
| `SuperKernel\HttpMessage\Response` | Class | A response; uses the standard reason phrase when none is given. |
| `SuperKernel\HttpMessage\Uri` | Class | A URI parsed from a string. |
| `SuperKernel\HttpMessage\Message` | Class | The abstract base of requests and responses. |
| `SuperKernel\HttpMessage\Stream\StringStream` | Class | A stream over a string in a temporary stream. |
| `SuperKernel\HttpMessage\Stream\ResourceStream` | Class | A stream over an open native stream resource. |
| `SuperKernel\HttpMessage\Stream\FileStream` | Class | A stream over a file, read-only (`rb`) by default. |
| `SuperKernel\HttpMessage\Stream\IterableStream` | Class | A forward-only stream over an iterable of string chunks. |
| `SuperKernel\HttpMessage\Stream\CallbackStream` | Class | A forward-only stream over a reader callback. |
| `SuperKernel\HttpMessage\Stream\ReadOnlyStream` | Class | A view of another stream that rejects writes. |
| `SuperKernel\HttpMessage\UploadedFile` | Class | An uploaded file from a stream, a temporary path, or a failed upload. |
| `SuperKernel\HttpMessage\Context\RequestContext` | Class | Stores, reads, and clears the current server request. |
| `SuperKernel\HttpMessage\Context\ResponseContext` | Class | Stores, reads, and clears the current response. |
| `Psr\Http\Message\ServerRequestInterface` | Interface | Injected as a service whose calls go to the current server request. |
| `Psr\Http\Message\ResponseInterface` | Interface | Injected as a service whose calls go to the current response. |

## Behavior to know

- A `with*()` method returns a changed copy and leaves the original unchanged. The copy shares the body stream, so reading the body through one moves the cursor seen by the other.
- Invalid input raises `InvalidArgumentException`, for example `Invalid HTTP status code 600.` or `Invalid HTTP header name Bad Name.`; a failed stream or upload operation raises `RuntimeException`.
- A stream owns the native resource it holds: `close()` closes it, and `detach()` hands it to the caller. Afterwards the stream raises `RuntimeException` for every operation that needs the resource; casting it to a string returns `''` and never throws.
- `read()` on an iterable or callback stream returns as soon as the source produces bytes, so it may return fewer bytes than requested.
- After a successful `moveTo()`, `getStream()` and a second `moveTo()` raise `The uploaded file has already been moved.`
- Calling an injected `ServerRequestInterface` when no request is stored raises `The current HTTP request context has not been initialized.`, and an injected `ResponseInterface` raises `The current HTTP response context has not been initialized.` The services are found in the build metadata: run `skernel build` after installing the package, or start with `skernel run`, which builds first.

## Documentation

The developer documentation covers every stream, uploads, URI normalization, the current-request services, and the error messages: [SuperKernel HTTP Message](https://github.com/super-kernel/.github/blob/main/docs/components/http-message.md).

## License

Released under the MIT License. See [LICENSE](LICENSE).
