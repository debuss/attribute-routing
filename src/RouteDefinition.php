<?php declare(strict_types=1);

namespace Routing;

use function implode;

/**
 * Represents a route definition, including the HTTP methods, path, handler, name, priority and middlewares.
 *
 * This route definition is to be used to register routes inside your router like FastRoute, Aura Router, ...
 */
final readonly class RouteDefinition
{

    /**
     * The route name, generated from the path and the HTTP methods when not given, like the Mezzio router does:
     * `/users/{id}^GET:POST`, or `/users/{id}` when there is no HTTP method.
     */
    public string $name;

    public function __construct(
        /** @var string[] $methods */
        public array $methods,
        public string $path,
        public mixed $handler,
        string $name = '',
        public int $priority = 0,
        /** @var string[] $middlewares */
        public array $middlewares = []
    ) {
        if ($name === '') {
            $name = $methods === [] ? $path : $path . '^' . implode(':', $methods);
        }

        $this->name = $name;
    }
}
