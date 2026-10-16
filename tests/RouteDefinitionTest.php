<?php declare(strict_types=1);

namespace Routing\Tests;

use Error;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use Routing\RouteDefinition;

#[CoversClass(RouteDefinition::class)]
final class RouteDefinitionTest extends TestCase
{

    public function testNamePriorityAndMiddlewaresHaveDefaultValues(): void
    {
        $route = new RouteDefinition(['GET'], '/users', 'handler');

        $this->assertSame(['GET'], $route->methods);
        $this->assertSame('/users', $route->path);
        $this->assertSame('handler', $route->handler);
        $this->assertSame('/users^GET', $route->name);
        $this->assertSame(0, $route->priority);
        $this->assertSame([], $route->middlewares);
    }

    /**
     * @return iterable<string, array{string[], string, string}>
     */
    public static function defaultNameProvider(): iterable
    {
        yield 'single HTTP method' => [['GET'], '/users/{id}', '/users/{id}^GET'];
        yield 'several HTTP methods, in their order' => [['GET', 'POST', 'DELETE'], '/api/user/{id}', '/api/user/{id}^GET:POST:DELETE'];
        yield 'no HTTP method' => [[], '/users', '/users'];
        yield 'root path' => [['GET'], '/', '/^GET'];
    }

    /**
     * @param string[] $methods
     */
    #[DataProvider('defaultNameProvider')]
    public function testTheNameIsGeneratedFromThePathAndTheHttpMethodsWhenNotGiven(array $methods, string $path, string $expectedName): void
    {
        $this->assertSame($expectedName, (new RouteDefinition($methods, $path, 'handler'))->name);
    }

    public function testAGivenNameIsKept(): void
    {
        $this->assertSame('users.show', (new RouteDefinition(['GET'], '/users/{id}', 'handler', 'users.show'))->name);
    }

    public function testItExposesAllItsProperties(): void
    {
        $handler = static fn() => null;
        $route = new RouteDefinition(['GET', 'HEAD'], '/users/{id}', $handler, 'users.show', -3, ['App\\Middleware\\Auth']);

        $this->assertSame(['GET', 'HEAD'], $route->methods);
        $this->assertSame('/users/{id}', $route->path);
        $this->assertSame($handler, $route->handler);
        $this->assertSame('users.show', $route->name);
        $this->assertSame(-3, $route->priority);
        $this->assertSame(['App\\Middleware\\Auth'], $route->middlewares);
    }

    public function testItIsReadonly(): void
    {
        $route = new RouteDefinition(['GET'], '/users', 'handler');

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cannot modify readonly property');

        // @phpstan-ignore property.readOnlyAssignOutOfClass (intentional, checks the property is readonly)
        $route->path = '/other';
    }
}
