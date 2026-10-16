<?php declare(strict_types=1);

namespace Routing;

use LogicException;
use function array_merge, array_values, usort;

/**
 * Merges the route definitions of several loaders, for instance one `AttributeRouteLoader` per module.
 *
 * The routes are checked for conflicts across all the loaders, and sorted by descending priority: routes sharing the
 * same priority keep the order of the loaders, then the order of each loader.
 */
final readonly class ChainRouteLoader implements RouteLoaderInterface
{

    /** @var RouteLoaderInterface[] */
    private array $loaders;

    public function __construct(RouteLoaderInterface ...$loaders)
    {
        $this->loaders = array_values($loaders);
    }

    /**
     * @return RouteDefinition[]
     * @throws LogicException If two routes, from the same loader or not, have the same name, or the same HTTP method
     *                        and path.
     */
    public function getRouteDefinitions(): array
    {
        $routeDefinitions = [];
        foreach ($this->loaders as $loader) {
            $routeDefinitions[] = $loader->getRouteDefinitions();
        }

        $routeDefinitions = array_merge([], ...$routeDefinitions);

        (new RouteConflictChecker())->check($routeDefinitions);

        // usort() is stable: routes sharing the same priority keep the order of the loaders
        usort($routeDefinitions, fn(RouteDefinition $a, RouteDefinition $b) => $b->priority <=> $a->priority);

        return $routeDefinitions;
    }
}
