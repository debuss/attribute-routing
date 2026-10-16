<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures;

use Routing\{RouteDefinition, ResourceAwareRouteLoaderInterface};

final class ResourceAwareRouteLoaderStub implements ResourceAwareRouteLoaderInterface
{

    public int $calls = 0;

    /**
     * @param RouteDefinition[] $routes
     * @param string[] $resources
     */
    public function __construct(public array $routes = [], public array $resources = []) {}

    public function getRouteDefinitions(): array
    {
        $this->calls++;

        return $this->routes;
    }

    public function getResources(): array
    {
        return $this->resources;
    }
}
