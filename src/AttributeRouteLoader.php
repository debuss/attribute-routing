<?php

namespace Routing;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use Routing\Attribute\{Controller, HttpMethod};
use SplFileInfo;
use function array_filter, array_merge, explode, implode, rtrim, str_replace, trim;

readonly class AttributeRouteLoader
{

    private string $namespace;
    private string $path;

    public function __construct(string $namespace, string $path)
    {
        $this->namespace = rtrim($namespace, '\\') . '\\';
        $this->path = rtrim($path, '\\/');
    }

    /**
     * @return RouteDefinition[]
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
            $relativePath = str_replace([$this->path, '.php', '/'], ['', '', '\\'], $file->getPathname());
            $className = $this->namespace . trim($relativePath, '\\');

            if (!class_exists($className)) {
                continue;
            }

            $controllerPrefix = '';

            $reflectionClass = new ReflectionClass($className);
            $controllerAttribute = $reflectionClass->getAttributes(Controller::class, ReflectionAttribute::IS_INSTANCEOF);

            if (count($controllerAttribute) > 0) {
                /** @var Controller $controllerInstance */
                $controllerInstance = $controllerAttribute[0]->newInstance();
                $controllerPrefix = trim($controllerInstance->prefix, '/');
            }

            foreach ($reflectionClass->getMethods() as $method) {
                $attributes = $method->getAttributes(HttpMethod::class, ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    /** @var HttpMethod $methodInstance */
                    $methodInstance = $attribute->newInstance();

                    $pathSegment = array_merge(
                        explode('/', $controllerPrefix),
                        explode('/', trim($methodInstance->path, '/'))
                    );

                    $path = '/'.implode('/', array_filter($pathSegment, fn($segment) => $segment !== ''));

                    $routeDefinitions[] = new RouteDefinition(
                        $methodInstance->methods,
                        $path,
                        [$reflectionClass->getName(), $method->getName()],
                        $methodInstance->name,
                        $methodInstance->priority
                    );
                }
            }
        }

        return $routeDefinitions;
    }
}
