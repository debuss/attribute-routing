<?php declare(strict_types=1);

namespace Routing\Tests;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use Routing\AttributeRouteLoader;
use Routing\RouteDefinition;
use Routing\Tests\Fixtures\Basic\Admin\DashboardController;
use Routing\Tests\Fixtures\Basic\Api\ProductController;
use Routing\Tests\Fixtures\Basic\{ChildController, UserController};
use Routing\Tests\Fixtures\NonPublic\PrivateMethod\PrivateRouteController;
use Routing\Tests\Fixtures\NonPublic\ProtectedMethod\ProtectedRouteController;
use Routing\Tests\Fixtures\NotAConcreteClass\AbstractType\AbstractController;
use Routing\Tests\Fixtures\NotAConcreteClass\EnumType\EnumController;
use Routing\Tests\Fixtures\NotAConcreteClass\InterfaceType\InterfaceController;
use Routing\Tests\Fixtures\NotAConcreteClass\TraitType\TraitController;
use function array_column, implode, ksort, sort, sprintf, str_replace;

#[CoversClass(AttributeRouteLoader::class)]
final class AttributeRouteLoaderTest extends TestCase
{

    private const string FIXTURES_NAMESPACE = 'Routing\\Tests\\Fixtures\\';
    private const string FIXTURES_PATH = __DIR__ . '/Fixtures/';

    public function testItLoadsEveryRouteOfTheControllers(): void
    {
        $this->assertSame(
            [
                'DELETE /users/{id} ' . UserController::class . '::destroy users.destroy 0',
                'GET /admin/dashboard ' . DashboardController::class . '::index admin.dashboard 0',
                'GET /admin/health ' . DashboardController::class . '::health  0',
                'GET /api/v1/products/{id} ' . ProductController::class . '::show products.show 0',
                'GET /child/from-trait ' . ChildController::class . '::fromTrait from-trait 0',
                'GET /child/inherited ' . ChildController::class . '::inherited inherited 0',
                'GET /users ' . UserController::class . '::index users.index 0',
                'GET /users/{id} ' . UserController::class . '::show users.show 0',
                'GET,POST /users/search ' . UserController::class . '::search users.search 0',
                'HEAD /api/v1/products/{id} ' . ProductController::class . '::show products.head 0',
                'OPTIONS /api/v1/products ' . ProductController::class . '::options  0',
                'PATCH /api/v1/products/{id} ' . ProductController::class . '::update  0',
                'POST /users ' . UserController::class . '::store users.store 0',
                'PUT /api/v1/products/{id} ' . ProductController::class . '::update  0',
            ],
            $this->describe($this->load('Basic'))
        );
    }

    public function testItReturnsRouteDefinitionInstances(): void
    {
        $routes = $this->load('Basic');

        $this->assertNotEmpty($routes);
        $this->assertContainsOnlyInstancesOf(RouteDefinition::class, $routes);
    }

    public function testTheHandlerIsACallableArrayOfClassNameAndMethodName(): void
    {
        $routes = array_column($this->load('Basic'), null, 'name');

        $this->assertSame([UserController::class, 'show'], $routes['users.show']->handler);
    }

    public function testCustomAttributesExtendingAsControllerAreSupported(): void
    {
        $routes = array_column($this->load('Basic'), null, 'name');

        $this->assertSame('/admin/dashboard', $routes['admin.dashboard']->path);
        $this->assertSame([DashboardController::class, 'index'], $routes['admin.dashboard']->handler);
    }

    public function testClassesWithoutAsControllerAttributeAreIgnored(): void
    {
        $this->assertNotContains('/not-a-controller', array_column($this->load('Basic'), 'path'));
    }

    public function testRoutesOfAbstractClassesAreInheritedByTheConcreteControllersExtendingThem(): void
    {
        $routes = array_column($this->load('Basic'), null, 'name');

        $this->assertSame('/child/inherited', $routes['inherited']->path);
        $this->assertSame([ChildController::class, 'inherited'], $routes['inherited']->handler);
    }

    public function testRoutesOfTraitsAreInheritedByTheControllersUsingThem(): void
    {
        $routes = array_column($this->load('Basic'), null, 'name');

        $this->assertSame('/child/from-trait', $routes['from-trait']->path);
        $this->assertSame([ChildController::class, 'fromTrait'], $routes['from-trait']->handler);
    }

    public function testAnEmptyDirectoryReturnsNoRoutes(): void
    {
        $this->assertSame([], $this->load('Empty'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pathProvider(): iterable
    {
        yield 'no prefix, leading slash' => ['no-prefix.leading-slash', '/users'];
        yield 'no prefix, trailing slash' => ['no-prefix.trailing-slash', '/users'];
        yield 'no prefix, root path' => ['no-prefix.root', '/'];
        yield 'no prefix, empty path' => ['no-prefix.empty', '/'];
        yield 'no prefix, omitted path' => ['no-prefix.omitted', '/'];
        yield 'prefix, root path' => ['slashed-prefix.root', '/users'];
        yield 'prefix, empty path' => ['slashed-prefix.empty', '/users'];
        yield 'prefix, omitted path' => ['slashed-prefix.omitted', '/users'];
        yield 'prefix, parameter' => ['slashed-prefix.param', '/users/{id}'];
        yield 'prefix and path without slashes' => ['bare-prefix.products', '/api/v1/products'];
        yield 'repeated slashes are collapsed' => ['messy-prefix.item', '/shop/catalog/items/{id}'];
    }

    #[DataProvider('pathProvider')]
    public function testThePathIsBuiltFromTheControllerPrefixAndTheMethodPath(string $routeName, string $expectedPath): void
    {
        $routes = array_column($this->load('Paths'), null, 'name');

        $this->assertArrayHasKey($routeName, $routes);
        $this->assertSame($expectedPath, $routes[$routeName]->path);
    }

    public function testThePriorityIsTheSumOfTheControllerAndMethodPriorities(): void
    {
        $priorities = array_column($this->load('Priority'), 'priority', 'name');
        ksort($priorities);

        $this->assertSame(
            [
                'high.boosted' => 15,
                'high.default' => 10,
                'high.lowered' => -10,
                'low.boosted' => 1,
                'low.default' => 0,
                'zero' => 0,
            ],
            $priorities
        );
    }

    public function testRoutesAreSortedByDescendingPriority(): void
    {
        $this->assertSame(
            [15, 10, 1, 0, 0, -10],
            array_column($this->load('Priority'), 'priority')
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nonPublicMethodProvider(): iterable
    {
        yield 'private method' => ['NonPublic/PrivateMethod', PrivateRouteController::class];
        yield 'protected method' => ['NonPublic/ProtectedMethod', ProtectedRouteController::class];
    }

    #[DataProvider('nonPublicMethodProvider')]
    public function testARouteOnANonPublicMethodThrowsAnException(string $fixture, string $className): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The method "%s::hidden()" has a route attribute but is not public.', $className));

        $this->load($fixture);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function notAConcreteClassProvider(): iterable
    {
        yield 'enum' => ['NotAConcreteClass/EnumType', 'enum', EnumController::class];
        yield 'interface' => ['NotAConcreteClass/InterfaceType', 'interface', InterfaceController::class];
        yield 'trait' => ['NotAConcreteClass/TraitType', 'trait', TraitController::class];
        yield 'abstract class' => ['NotAConcreteClass/AbstractType', 'abstract class', AbstractController::class];
    }

    #[DataProvider('notAConcreteClassProvider')]
    public function testAControllerAttributeOnAnythingElseThanAConcreteClassThrowsAnException(string $fixture, string $kind, string $name): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The %s "%s" has a controller attribute but only concrete classes can be controllers.', $kind, $name));

        $this->load($fixture);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function separatorProvider(): iterable
    {
        yield 'no trailing separators' => ['Routing\\Tests\\Fixtures\\Basic', __DIR__ . '/Fixtures/Basic'];
        yield 'trailing namespace separator' => ['Routing\\Tests\\Fixtures\\Basic\\', __DIR__ . '/Fixtures/Basic'];
        yield 'trailing slash' => ['Routing\\Tests\\Fixtures\\Basic', __DIR__ . '/Fixtures/Basic/'];
        yield 'trailing directory separator' => ['Routing\\Tests\\Fixtures\\Basic', __DIR__ . '/Fixtures/Basic' . DIRECTORY_SEPARATOR];
        yield 'trailing separators everywhere' => ['Routing\\Tests\\Fixtures\\Basic\\', __DIR__ . '/Fixtures/Basic//'];
    }

    #[DataProvider('separatorProvider')]
    public function testTrailingSeparatorsOfNamespaceAndPathAreIgnored(string $namespace, string $path): void
    {
        $loader = new AttributeRouteLoader($namespace, $path);

        $this->assertSame(
            $this->describe($this->load('Basic')),
            $this->describe($loader->getRouteDefinitions())
        );
    }

    public function testANamespaceThatDoesNotMatchThePathReturnsNoRoutes(): void
    {
        $loader = new AttributeRouteLoader('Some\\Other\\Namespace', self::FIXTURES_PATH . 'Basic');

        $this->assertSame([], $loader->getRouteDefinitions());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPathProvider(): iterable
    {
        yield 'non-existent directory' => [__DIR__ . '/Fixtures/DoesNotExist'];
        yield 'file instead of directory' => [__FILE__];
        yield 'empty path' => [''];
    }

    #[DataProvider('invalidPathProvider')]
    public function testAnInvalidPathThrowsAnException(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The path "%s" is not an existing directory.', $path));

        new AttributeRouteLoader(self::FIXTURES_NAMESPACE, $path);
    }

    /**
     * @return RouteDefinition[]
     */
    private function load(string $fixture): array
    {
        $loader = new AttributeRouteLoader(
            self::FIXTURES_NAMESPACE . str_replace('/', '\\', $fixture),
            self::FIXTURES_PATH . $fixture
        );

        return $loader->getRouteDefinitions();
    }

    /**
     * Turns route definitions into sorted, human-readable strings, so that assertions do not depend on the
     * filesystem iteration order (which is not guaranteed) and failures display a readable diff.
     *
     * @param RouteDefinition[] $routes
     * @return string[]
     */
    private function describe(array $routes): array
    {
        $descriptions = [];
        foreach ($routes as $route) {
            $this->assertIsArray($route->handler);
            $this->assertContainsOnlyString($route->handler);

            $descriptions[] = implode(' ', [
                implode(',', $route->methods),
                $route->path,
                implode('::', $route->handler),
                $route->name,
                $route->priority
            ]);
        }

        sort($descriptions);

        return $descriptions;
    }
}
