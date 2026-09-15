<?php declare(strict_types=1);

namespace Routing\Tests\Attribute;

use Attribute;
use Error;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Routing\Attribute\Route;

#[CoversClass(Route::class)]
final class RouteTest extends TestCase
{

    public function testPathNameAndPriorityHaveDefaultValues(): void
    {
        $route = new Route('GET');

        $this->assertSame('', $route->path);
        $this->assertSame('', $route->name);
        $this->assertSame(0, $route->priority);
    }

    public function testItAcceptsNameAndPriority(): void
    {
        $route = new Route('GET', '/users/{id}', name: 'users.show', priority: 42);

        $this->assertSame('/users/{id}', $route->path);
        $this->assertSame('users.show', $route->name);
        $this->assertSame(42, $route->priority);
    }

    /**
     * @return iterable<string, array{string|string[], string[]}>
     */
    public static function methodsProvider(): iterable
    {
        yield 'single method as string' => ['GET', ['GET']];
        yield 'single method as array' => [['GET'], ['GET']];
        yield 'several methods' => [['GET', 'POST'], ['GET', 'POST']];
        yield 'lowercase methods are uppercased' => [['get', 'Post'], ['GET', 'POST']];
        yield 'array keys are dropped' => [['read' => 'GET', 'write' => 'POST'], ['GET', 'POST']];
    }

    /**
     * @param string|string[] $methods
     * @param string[] $expectedMethods
     */
    #[DataProvider('methodsProvider')]
    public function testItNormalizesTheHttpMethods(string|array $methods, array $expectedMethods): void
    {
        $this->assertSame($expectedMethods, (new Route($methods, '/path'))->methods);
    }

    public function testItRequiresAtLeastOneHttpMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A route must define at least one HTTP method.');

        new Route([], '/path');
    }

    public function testItIsARepeatableMethodAttribute(): void
    {
        $attributes = (new ReflectionClass(Route::class))->getAttributes(Attribute::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE, $attributes[0]->newInstance()->flags);
    }

    public function testItIsReadonly(): void
    {
        $route = new Route('GET', '/path');

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cannot modify readonly property');

        // @phpstan-ignore property.readOnlyAssignOutOfClass (intentional, checks the property is readonly)
        $route->path = '/other';
    }
}
