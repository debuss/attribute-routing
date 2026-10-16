<?php declare(strict_types=1);

namespace Routing;

/**
 * A loader able to tell which files its route definitions come from, so that `CachedRouteLoader` can regenerate its
 * cache file when they change (in debug mode).
 */
interface ResourceAwareRouteLoaderInterface extends RouteLoaderInterface
{

    /**
     * @return string[] Paths of the files and directories (tracked recursively) the route definitions come from.
     */
    public function getResources(): array;
}
