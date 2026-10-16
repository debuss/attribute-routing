<?php declare(strict_types=1);

namespace Routing\Tests;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Routing\{AttributeRouteLoader, ChainRouteLoader, RouteDefinition};
use Routing\Tests\Fixtures\RouteLoaderStub;
use function array_column, count, rsort;

#[CoversClass(ChainRouteLoader::class)]
final class ChainRouteLoaderTest extends TestCase
{

    public function testAnEmptyChainReturnsNoRoutes(): void
    {
        $this->assertSame([], (new ChainRouteLoader())->getRouteDefinitions());
    }

    public function testItMergesTheRoutesOfEveryLoaderCallingEachOfThemOnce(): void
    {
        $first = new RouteLoaderStub([new RouteDefinition(['GET'], '/a', 'a'), new RouteDefinition(['GET'], '/b', 'b')]);
        $second = new RouteLoaderStub([]);
        $third = new RouteLoaderStub([new RouteDefinition(['GET'], '/c', 'c')]);

        $routes = (new ChainRouteLoader($first, $second, $third))->getRouteDefinitions();

        $this->assertSame(['/a', '/b', '/c'], array_column($routes, 'path'));
        $this->assertSame([1, 1, 1], [$first->calls, $second->calls, $third->calls]);
    }

    public function testRoutesAreSortedByDescendingPriorityAcrossLoaders(): void
    {
        $first = new RouteLoaderStub([
            new RouteDefinition(['GET'], '/first-high', 'a', priority: 10),
            new RouteDefinition(['GET'], '/first-low', 'b', priority: -5),
        ]);
        $second = new RouteLoaderStub([
            new RouteDefinition(['GET'], '/second-highest', 'c', priority: 20),
            new RouteDefinition(['GET'], '/second-high', 'd', priority: 10),
        ]);

        $routes = (new ChainRouteLoader($first, $second))->getRouteDefinitions();

        $this->assertSame(['/second-highest', '/first-high', '/second-high', '/first-low'], array_column($routes, 'path'));
    }

    public function testRoutesSharingTheSamePriorityKeepTheOrderOfTheLoadersThenOfEachLoader(): void
    {
        $first = new RouteLoaderStub([new RouteDefinition(['GET'], '/z', 'z'), new RouteDefinition(['GET'], '/y', 'y')]);
        $second = new RouteLoaderStub([new RouteDefinition(['GET'], '/b', 'b'), new RouteDefinition(['GET'], '/a', 'a')]);

        $this->assertSame(
            ['/b', '/a', '/z', '/y'],
            array_column((new ChainRouteLoader($second, $first))->getRouteDefinitions(), 'path')
        );
    }

    public function testRoutesWithTheSameNameInTwoLoadersThrowAnException(): void
    {
        $first = new RouteLoaderStub([new RouteDefinition(['GET'], '/blog', 'BlogController::index', 'index')]);
        $second = new RouteLoaderStub([new RouteDefinition(['GET'], '/shop', 'ShopController::index', 'index')]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The route name "index" is used by both "BlogController::index" and "ShopController::index".');

        (new ChainRouteLoader($first, $second))->getRouteDefinitions();
    }

    public function testRoutesWithTheSameHttpMethodAndPathInTwoLoadersThrowAnException(): void
    {
        $first = new RouteLoaderStub([new RouteDefinition(['GET'], '/', 'BlogController::index')]);
        $second = new RouteLoaderStub([new RouteDefinition(['GET', 'HEAD'], '/', 'ShopController::index')]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The route "GET /" is defined by both "BlogController::index" and "ShopController::index".');

        (new ChainRouteLoader($first, $second))->getRouteDefinitions();
    }

    public function testItChainsAttributeRouteLoaders(): void
    {
        $basic = new AttributeRouteLoader('Routing\\Tests\\Fixtures\\Basic', __DIR__ . '/Fixtures/Basic');
        $priority = new AttributeRouteLoader('Routing\\Tests\\Fixtures\\Priority', __DIR__ . '/Fixtures/Priority');

        $routes = (new ChainRouteLoader($basic, $priority))->getRouteDefinitions();

        $this->assertCount(count($basic->getRouteDefinitions()) + count($priority->getRouteDefinitions()), $routes);

        $priorities = array_column($routes, 'priority');
        $sortedPriorities = $priorities;
        rsort($sortedPriorities);
        $this->assertSame($sortedPriorities, $priorities);
    }
}
