<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use Error;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use stdClass;
use SuperKernel\Attribute\Provider;
use SuperKernel\HttpMessage\Context\RequestContext;
use SuperKernel\HttpMessage\Context\ResponseContext;
use SuperKernel\HttpMessage\Provider\RequestProvider;
use SuperKernel\HttpMessage\Provider\ResponseProvider;
use SuperKernel\HttpMessage\Response;
use SuperKernel\HttpMessage\ServerRequest;
use Throwable;

use function is_callable;

final class MessageProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        RequestContext::clear();
        ResponseContext::clear();

        parent::tearDown();
    }

    /**
     * These assertions protect the public interface contract even when static types already imply it.
     *
     * @throws Throwable When fixture setup or the operation under test throws
     *
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testRequestProviderDelegatesToTheCurrentRequestContext(): void
    {
        $context = new ServerRequest(
            method: 'POST',
            uri: '/provider',
            headers: [
                'X-Request-Id' => '42',
            ],
        );
        RequestContext::set($context);
        $request = new RequestProvider();
        $reflection = new ReflectionClass($request);

        self::assertProvider(RequestProvider::class, ServerRequestInterface::class);
        self::assertInstanceOf(ServerRequestInterface::class, $request);
        self::assertTrue($reflection->getMethod('delegate')->isPrivate());
        self::assertTrue($reflection->getMethod('request')->isPrivate());
        self::assertFalse($reflection->hasMethod('__call'));
        self::assertFalse($reflection->hasMethod('serverRequest'));
        self::assertFalse($reflection->hasMethod('nativeRequest'));
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/provider', $request->getUri()->getPath());
        self::assertSame('42', $request->getHeaderLine('X-Request-Id'));

        $modified = $request->withAttribute('provider', true);

        self::assertInstanceOf(ServerRequest::class, $modified);
        self::assertTrue($modified->getAttribute('provider'));
    }

    /**
     * These assertions protect the public interface contract even when static types already imply it.
     *
     * @throws Throwable When fixture setup or the operation under test throws
     *
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testResponseProviderDelegatesToTheCurrentResponseContext(): void
    {
        ResponseContext::set(new Response());
        $response = new ResponseProvider();
        $reflection = new ReflectionClass($response);

        self::assertProvider(ResponseProvider::class, ResponseInterface::class);
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertTrue($reflection->getMethod('delegate')->isPrivate());
        self::assertTrue($reflection->getMethod('response')->isPrivate());
        self::assertFalse($reflection->hasMethod('__call'));
        self::assertFalse($reflection->hasMethod('nativeResponse'));
        self::assertSame(200, $response->getStatusCode());

        $modified = $response->withStatus(202)->withHeader('X-Provider', 'yes');

        self::assertInstanceOf(Response::class, $modified);
        self::assertSame(202, $modified->getStatusCode());
        self::assertSame('yes', $modified->getHeaderLine('X-Provider'));
    }

    /**
     * Calls an intentionally absent method to verify the PSR-only boundary.
     *
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testRequestProviderDoesNotForwardMethodsOutsideThePsrContract(): void
    {
        RequestContext::set(new class() extends ServerRequest {
            public function __construct()
            {
                parent::__construct('GET', '/');
            }

            public function nativeRequest(): object
            {
                return new stdClass();
            }
        });
        $request = new RequestProvider();

        self::assertFalse(is_callable([
            $request,
            'nativeRequest',
        ]));

        $this->expectException(Error::class);

        $request->nativeRequest();
    }

    /**
     * Calls an intentionally absent method to verify the PSR-only boundary.
     *
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testResponseProviderDoesNotForwardMethodsOutsideThePsrContract(): void
    {
        ResponseContext::set(new class() extends Response {
            public function nativeResponse(): object
            {
                return new stdClass();
            }
        });
        $response = new ResponseProvider();

        self::assertFalse(is_callable([
            $response,
            'nativeResponse',
        ]));

        $this->expectException(Error::class);

        $response->nativeResponse();
    }

    /**
     * @param class-string $provider
     * @param class-string $contract
     *
     * @throws Throwable When fixture setup or a supplied test callback fails
     */
    private static function assertProvider(string $provider, string $contract): void
    {
        $attributes = new ReflectionClass($provider)->getAttributes(Provider::class);

        self::assertCount(1, $attributes);
        self::assertSame($contract, $attributes[0]->newInstance()->className);
    }
}
