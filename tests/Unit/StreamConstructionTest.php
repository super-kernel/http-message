<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;
use ReflectionMethod;
use SuperKernel\HttpMessage\Stream\CallbackStream;
use SuperKernel\HttpMessage\Stream\FileStream;
use SuperKernel\HttpMessage\Stream\IterableStream;
use SuperKernel\HttpMessage\Stream\ReadOnlyStream;
use SuperKernel\HttpMessage\Stream\ResourceStream;
use SuperKernel\HttpMessage\Stream\StringStream;
use Throwable;

use function sprintf;
use function str_starts_with;

final class StreamConstructionTest extends TestCase
{
    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testEveryStreamScenarioUsesAFinalClassWithAPublicConstructorAndNoStaticFactory(): void
    {
        foreach ([
            StringStream::class,
            ResourceStream::class,
            FileStream::class,
            IterableStream::class,
            CallbackStream::class,
            ReadOnlyStream::class,
        ] as $className) {
            $reflection = new ReflectionClass($className);
            $constructor = $reflection->getConstructor();

            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->implementsInterface(StreamInterface::class));
            self::assertNotNull($constructor);
            self::assertTrue($constructor->isPublic());

            foreach ($reflection->getMethods(ReflectionMethod::IS_STATIC) as $method) {
                self::assertFalse(
                    str_starts_with($method->getName(), 'from'),
                    sprintf('%s must not expose the static factory %s().', $className, $method->getName()),
                );
            }
        }
    }
}
