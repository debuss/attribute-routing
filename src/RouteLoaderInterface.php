<?php declare(strict_types=1);

namespace Routing;

/**
 * Provides the route definitions to register inside your router.
 */
interface RouteLoaderInterface
{

    /**
     * @return RouteDefinition[]
     */
    public function getRouteDefinitions(): array;
}
