<?php declare(strict_types=1);

namespace Routing;

use FilesystemIterator;
use InvalidArgumentException;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use Routing\Attribute\{AsController, Middleware, Route};
use ReflectionException;
use ReflectionMethod;
use SplFileInfo;
use function array_filter, array_map, array_merge, class_exists, count, explode, implode, interface_exists, is_dir, rtrim, sort, sprintf, str_replace, strlen, substr, trait_exists, trim, usort;

readonly class AttributeRouteLoader implements RouteLoaderInterface
{

    private string $namespace;
    private string $path;

    /**
     * @throws InvalidArgumentException If `$path` is not an existing directory.
     */
    public function __construct(string $namespace, string $path)
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException(sprintf('The path "%s" is not an existing directory.', $path));
        }

        $this->namespace = rtrim($namespace, '\\') . '\\';
        $this->path = rtrim($path, '\\/');
    }

    /**
     * @return RouteDefinition[]
     * @throws LogicException|ReflectionException If a controller attribute is set on an interface, a trait, an enum or
     *                                            an abstract class, if a route attribute is set on a non-public method,
     *                                            or if two routes have the same name, or the same HTTP method and path.
     */
    public function getRouteDefinitions(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS)
        );

        $classNames = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            // Convert file path to Class Name (PSR-4 assumption)
            $relativePath = substr($file->getPathname(), strlen($this->path) + 1, -strlen('.php'));
            $classNames[] = $this->namespace . str_replace(['/', '\\'], '\\', $relativePath);
        }

        // The filesystem iteration order is not guaranteed, sorting the class names makes the routes order deterministic
        sort($classNames, SORT_STRING);

        $routeDefinitions = [];

        foreach ($classNames as $className) {
            // The autoloader is only triggered once, by class_exists()
            if (!class_exists($className) && !interface_exists($className, false) && !trait_exists($className, false)) {
                continue;
            }

            $reflectionClass = new ReflectionClass($className);

            $controllerAttributes = $reflectionClass->getAttributes(AsController::class, ReflectionAttribute::IS_INSTANCEOF);
            if (count($controllerAttributes) === 0) {
                continue;
            }

            if ($reflectionClass->isInterface() || $reflectionClass->isTrait() || $reflectionClass->isEnum() || $reflectionClass->isAbstract()) {
                throw new LogicException(sprintf(
                    'The %s "%s" has a controller attribute but only concrete classes can be controllers.',
                    match (true) {
                        $reflectionClass->isInterface() => 'interface',
                        $reflectionClass->isTrait() => 'trait',
                        $reflectionClass->isEnum() => 'enum',
                        default => 'abstract class',
                    },
                    $reflectionClass->getName()
                ));
            }

            /** @var AsController $controller */
            $controller = $controllerAttributes[0]->newInstance();
            $controllerPrefix = trim($controller->prefix, '/');
            $controllerMiddlewares = $this->getMiddlewares($reflectionClass);

            foreach ($reflectionClass->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);

                if (count($attributes) === 0) {
                    continue;
                }

                if (!$method->isPublic()) {
                    throw new LogicException(sprintf(
                        'The method "%s::%s()" has a route attribute but is not public.',
                        $reflectionClass->getName(),
                        $method->getName()
                    ));
                }

                $middlewares = array_merge($controllerMiddlewares, $this->getMiddlewares($method));

                foreach ($attributes as $attribute) {
                    /** @var Route $route */
                    $route = $attribute->newInstance();

                    $pathSegment = array_merge(
                        explode('/', $controllerPrefix),
                        explode('/', trim($route->path, '/'))
                    );

                    $path = '/' . implode('/', array_filter($pathSegment, fn($segment) => $segment !== ''));

                    $routeDefinitions[] = new RouteDefinition(
                        $route->methods,
                        $path,
                        [$reflectionClass->getName(), $method->getName()],
                        $route->name,
                        $controller->priority + $route->priority,
                        $middlewares
                    );
                }
            }
        }

        $this->checkConflicts($routeDefinitions);

        // usort() is stable: routes sharing the same priority keep their discovery order
        usort($routeDefinitions, fn(RouteDefinition $a, RouteDefinition $b) => $b->priority <=> $a->priority);

        return $routeDefinitions;
    }

    /**
     * @param RouteDefinition[] $routeDefinitions
     * @throws LogicException If two routes have the same name, or the same HTTP method and path.
     */
    private function checkConflicts(array $routeDefinitions): void
    {
        $handlersByName = [];
        $handlersByMethodAndPath = [];

        foreach ($routeDefinitions as $routeDefinition) {
            /** @var array{class-string, string} $handler */
            $handler = $routeDefinition->handler;
            $handler = sprintf('%s::%s()', ...$handler);

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
     * @param ReflectionClass<object>|ReflectionMethod $reflection
     * @return string[] The middlewares of every middleware attribute, in their order of declaration.
     */
    private function getMiddlewares(ReflectionClass|ReflectionMethod $reflection): array
    {
        $attributes = $reflection->getAttributes(Middleware::class, ReflectionAttribute::IS_INSTANCEOF);

        return array_merge([], ...array_map(
            fn(ReflectionAttribute $attribute) => $attribute->newInstance()->middlewares,
            $attributes
        ));
    }
}
