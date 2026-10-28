# SuperKernel HTTP Message

`super-kernel/http-message` provides PSR-7 message objects and request-scoped message access for SuperKernel. Its
message objects implement `psr/http-message` 2.0 and have no native server dependency.

The package provides:

- `Message`, the shared immutable message base;
- `Request` and `ServerRequest`;
- `Response`;
- `Uri`;
- string, native-resource, file, iterable, callback, and read-only stream implementations;
- `UploadedFile`;
- `Context\RequestContext` and `Context\ResponseContext` plus their stateless PSR DI providers.

## Source organization

Paths below are relative to `src/` and the package namespace.

| Directory | Responsibility |
| --- | --- |
| `Stream` | PSR stream implementations and their shared `ResourceStreamBehavior` trait. |
| `Context / Provider` | Typed request-local access and stateless PSR delegates. |

Implementation helpers retain an `@internal` boundary; a responsibility namespace does not make them extension points.

### Public namespaces

Use the following component declarations:

- `Stream\ResourceStreamBehavior`

## Basic usage

```php
use SuperKernel\HttpMessage\Request;
use SuperKernel\HttpMessage\Response;
use SuperKernel\HttpMessage\Stream\StringStream;

$request = new Request('GET', 'https://example.test/widgets?limit=10');
$request = $request->withHeader('Accept', 'application/json');

$response = new Response(
    statusCode: 200,
    headers: [
        'Content-Type' => 'application/json',
    ],
    body: new StringStream('{"items":[]}'),
);
```

Messages, requests, responses, and URIs use PSR-7 copy-on-write methods. A changed value is returned as a new object;
the original object remains unchanged. Bodies intentionally retain PSR-7 stream identity and may therefore have a
mutable cursor.

## Streams and uploads

Create one stream class for each source scenario through its public constructor:

```php
use SuperKernel\HttpMessage\Stream\FileStream;
use SuperKernel\HttpMessage\Stream\ResourceStream;
use SuperKernel\HttpMessage\Stream\StringStream;

$memory = new StringStream('payload');
$resource = new ResourceStream($nativeResource);
$file = new FileStream('/srv/application/data.json');
```

Each stream owns its attached native resource. `close()` closes it and `detach()` transfers the resource to the caller.
Detached or closed streams reject operations that require a resource. `StringStream`, `ResourceStream`, and
`FileStream` keep source creation separate while sharing only their internal resource lifecycle behavior.

Use behavior-specific implementations when the source is not naturally represented by one native resource:

```php
use SuperKernel\HttpMessage\Stream\CallbackStream;
use SuperKernel\HttpMessage\Stream\IterableStream;
use SuperKernel\HttpMessage\Stream\ReadOnlyStream;

$chunks = new IterableStream([
    'first',
    'second',
]);
$callback = new CallbackStream($reader);
$readOnly = new ReadOnlyStream($memory);
```

Iterable and callback streams are readable, forward-only, and report an unknown size. Iterable values must be strings;
callback readers receive the requested byte count and return a non-empty string or `null` at end of source. `read()`
returns as soon as the source has produced any bytes and never waits for the full requested length, so a slow producer
such as an event stream reaches its consumer one chunk at a time; a chunk larger than the request stays buffered for
later reads. Their `detach()` method releases the producer and returns `null`. `ReadOnlyStream` delegates reads,
seeks, metadata, closure, and detachment to another PSR stream while rejecting writes. All stream contents remain
bytes; serialization of arrays or objects belongs outside the stream layer.

`UploadedFile` accepts either a `StreamInterface`, a local temporary path, or no source for unsuccessful uploads.
After `moveTo()` succeeds, `getStream()` and a second move fail as required by PSR-7.

## Scoped access

`RequestContext` and `ResponseContext` store the current messages under the PSR interface keys in
`SuperKernel\Context\Context`. `Provider\RequestProvider` and `Provider\ResponseProvider` delegate each declared PSR
call to those current messages through private methods, so a retained provider does not capture invocation-specific
state. The providers implement and are registered directly for `Psr\Http\Message\ServerRequestInterface` and
`Psr\Http\Message\ResponseInterface`; they do not expose magic forwarding, context accessors, or native transport
objects.

## URI behavior

`Uri` normalizes schemes and hosts to lowercase, removes standard HTTP and HTTPS ports from the rendered authority,
preserves valid percent-encoded octets, and percent-encodes invalid component bytes without double encoding.

## Scope

This package does not provide `psr/http-factory` factories, routing, middleware plans, controller invocation, native
request adaptation, response emission, callback registration, or protocol exception recovery. Native runtime
integration remains an HTTP Server responsibility.

## Development

Use a PHP 8.4 Composer in this package's checkout:

```bash
composer install
composer validate --strict
composer test
composer format:check
composer format
```
