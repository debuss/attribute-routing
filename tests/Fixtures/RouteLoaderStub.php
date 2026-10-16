<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures;

use Routing\{RouteDefinition, RouteLoaderInterface};

final class RouteLoaderStub implements RouteLoaderInterface
{

    public int $calls = 0;

    /**
     * @param RouteDefinition[] $routes
     */
    public function __construct(public array $routes = []) {}

    public function getRouteDefinitions(): array
    {
        $this->calls++;

        return $this->routes;
    }
}
