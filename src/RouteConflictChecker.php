<?php declare(strict_types=1);

namespace Routing;

use LogicException;
use function array_is_list, count, get_debug_type, is_array, is_object, is_string, sprintf;

/**
 * Checks that route definitions do not conflict with each other.
 *
 * @internal
 */
final readonly class RouteConflictChecker
{

    /**
     * @param RouteDefinition[] $routeDefinitions
     * @throws LogicException If two routes have the same name, or the same HTTP method and path.
     */
    public function check(array $routeDefinitions): void
    {
        $handlersByName = [];
        $handlersByMethodAndPath = [];

        foreach ($routeDefinitions as $routeDefinition) {
            $handler = $this->describeHandler($routeDefinition->handler);

            if ($routeDefinition->name !== '') {
                if (isset($handlersByName[$routeDefinition->name])) {
                    throw new LogicException(sprintf(
                        'The route name "%s" is used by both "%s" and "%s".',
                        $routeDefinition->name,
                        $handlersByName[$routeDefinition->name],
                        $handler
                    ));
                }

                $handlersByName[$routeDefinition->name] = $handler;
            }

            foreach ($routeDefinition->methods as $method) {
                $methodAndPath = $method . ' ' . $routeDefinition->path;

                if (isset($handlersByMethodAndPath[$methodAndPath])) {
                    throw new LogicException(sprintf(
                        'The route "%s" is defined by both "%s" and "%s".',
                        $methodAndPath,
                        $handlersByMethodAndPath[$methodAndPath],
                        $handler
                    ));
                }

                $handlersByMethodAndPath[$methodAndPath] = $handler;
            }
        }
    }

    /**
     * Describes a handler in exception messages: `Class::method()` for callable arrays, as is for strings, and its type
     * otherwise.
     */
    private function describeHandler(mixed $handler): string
    {
        if (is_array($handler) && array_is_list($handler) && count($handler) === 2 && is_string($handler[1])
            && (is_string($handler[0]) || is_object($handler[0]))) {
            return sprintf('%s::%s()', is_object($handler[0]) ? $handler[0]::class : $handler[0], $handler[1]);
        }

        return is_string($handler) ? $handler : get_debug_type($handler);
    }
}
