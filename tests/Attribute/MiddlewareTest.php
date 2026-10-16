<?php declare(strict_types=1);

namespace Routing\Tests\Attribute;

use Attribute;
use Error;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Routing\Attribute\Middleware;
use Routing\Tests\Fixtures\Authenticated;

#[CoversClass(Middleware::class)]
final class MiddlewareTest extends TestCase
{

    public function testItAcceptsASingleMiddleware(): void
    {
        $this->assertSame(['App\\Middleware\\Auth'], (new Middleware('App\\Middleware\\Auth'))->middlewares);
    }

    public function testItAcceptsSeveralMiddlewaresAndKeepsTheirOrder(): void
    {
        $attribute = new Middleware('App\\Middleware\\Auth', 'App\\Middleware\\Csrf', 'App\\Middleware\\Auth');

        $this->assertSame(['App\\Middleware\\Auth', 'App\\Middleware\\Csrf', 'App\\Middleware\\Auth'], $attribute->middlewares);
    }

    public function testMiddlewaresAreAList(): void
    {
        $attribute = new Middleware(...['auth' => 'App\\Middleware\\Auth', 'csrf' => 'App\\Middleware\\Csrf']);

        $this->assertSame(['App\\Middleware\\Auth', 'App\\Middleware\\Csrf'], $attribute->middlewares);
    }

    public function testMiddlewaresAreNotValidated(): void
    {
        $this->assertSame(['auth:api'], (new Middleware('auth:api'))->middlewares);
    }

    /**
     * @return iterable<string, array{string[], string}>
     */
    public static function invalidMiddlewaresProvider(): iterable
    {
        yield 'no middleware' => [[], 'A middleware attribute must define at least one middleware.'];
        yield 'empty middleware' => [['App\\Middleware\\Auth', ''], 'A middleware cannot be an empty string.'];
    }

    /**
     * @param string[] $middlewares
     */
    #[DataProvider('invalidMiddlewaresProvider')]
    public function testInvalidMiddlewaresThrowAnException(array $middlewares, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new Middleware(...$middlewares);
    }

    public function testItIsARepeatableClassAndMethodAttribute(): void
    {
        $attributes = (new ReflectionClass(Middleware::class))->getAttributes(Attribute::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(
            Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE,
            $attributes[0]->newInstance()->flags
        );
    }

    public function testItCanBeExtendedToCreateCustomMiddlewareAttributes(): void
    {
        $this->assertSame(['App\\Middleware\\Authenticate'], (new Authenticated())->middlewares);
    }

    public function testItIsReadonly(): void
    {
        $attribute = new Middleware('App\\Middleware\\Auth');

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cannot modify readonly property');

        // @phpstan-ignore property.readOnlyAssignOutOfClass (intentional, checks the property is readonly)
        $attribute->middlewares = [];
    }
}
