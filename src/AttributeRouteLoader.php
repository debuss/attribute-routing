<?php declare(strict_types=1);

namespace Routing;

use FilesystemIterator;
use InvalidArgumentException;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use Routing\Attribute\{AsController, Route};
use ReflectionException;
use SplFileInfo;
use function array_filter, array_merge, class_exists, count, explode, implode, interface_exists, is_dir, rtrim, sprintf, str_replace, strlen, substr, trait_exists, trim, usort;

readonly class AttributeRouteLoader
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
     *                                            an abstract class, or if a route attribute is set on a non-public method.
     */
    public function getRouteDefinitions(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS)
        );

        $routeDefinitions = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            // Convert file path to Class Name (PSR-4 assumption)
            $relativePath = substr($file->getPathname(), strlen($this->path) + 1, -strlen('.php'));
            $className = $this->namespace . str_replace(['/', '\\'], '\\', $relativePath);

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

            foreach ($reflectionClass->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);

                if (count($attributes) > 0 && !$method->isPublic()) {
                    throw new LogicException(sprintf(
                        'The method "%s::%s()" has a route attribute but is not public.',
                        $reflectionClass->getName(),
                        $method->getName()
                    ));
                }

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
                        $controller->priority + $route->priority
                    );
                }
            }
        }

        usort($routeDefinitions, fn(RouteDefinition $a, RouteDefinition $b) => $b->priority <=> $a->priority);

        return $routeDefinitions;
    }
}
