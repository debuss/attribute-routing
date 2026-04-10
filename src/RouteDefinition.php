<?php

namespace Routing;

/**
 * Represents a route definition, including the HTTP methods, path, handler, name, and priority.
 *
 * This route definition is to be used to register routes inside your router like FastRoute, Aura Router, ...
 */
final readonly class RouteDefinition
{

    public function __construct(
        /** @var string[] $methods */
        public array $methods,
        public string $path,
        public mixed $handler,
        public string $name = '',
        public int $priority = 0
    ) {}
}
