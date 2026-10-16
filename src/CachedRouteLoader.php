<?php declare(strict_types=1);

namespace Routing;

use FilesystemIterator;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use function array_is_list, bin2hex, clearstatcache, dirname, file_exists, file_put_contents, filemtime, filter_var, function_exists, get_debug_type, implode, ini_get, is_array, is_dir, is_file, is_scalar, mkdir, opcache_invalidate, random_bytes, rename, sprintf, unlink, var_export;

/**
 * Caches the route definitions of another loader in a PHP file.
 *
 * The first call generates the cache file, the next ones only `require` it, so that the decorated loader is not
 * called anymore. With OPcache enabled, the cache file is kept in shared memory and reading it costs nearly nothing.
 *
 * By default, the cache file is never invalidated automatically: call `clear()` or `warmUp()` when your routes change
 * (on deployment for instance). In debug mode, meant for development, the cache file is regenerated when a resource of
 * the decorated loader (see `ResourceAwareRouteLoaderInterface`) has been modified since its generation.
 */
final readonly class CachedRouteLoader implements RouteLoaderInterface
{

    /**
     * @param bool $debug Regenerates the cache file when a resource of the decorated loader has been modified since its
     *                    generation. It costs a `stat()` per file of the resources, so it is meant for development.
     */
    public function __construct(
        private RouteLoaderInterface $loader,
        private string $cacheFile,
        private bool $debug = false
    ) {}

    /**
     * @return RouteDefinition[]
     * @throws LogicException If a route handler cannot be exported to PHP code (only scalars, null and arrays of them can).
     * @throws RuntimeException If the cache file cannot be written.
     */
    public function getRouteDefinitions(): array
    {
        if (is_file($this->cacheFile) && (!$this->debug || $this->isFresh())) {
            /** @var RouteDefinition[] $routeDefinitions */
            $routeDefinitions = require $this->cacheFile;

            return $routeDefinitions;
        }

        return $this->generate();
    }

    /**
     * (Re)generates the cache file from the decorated loader, whether it already exists or not.
     *
     * @throws LogicException If a route handler cannot be exported to PHP code (only scalars, null and arrays of them can).
     * @throws RuntimeException If the cache file cannot be written.
     */
    public function warmUp(): void
    {
        $this->generate();
    }

    /**
     * Removes the cache file, so that the next call to `getRouteDefinitions()` regenerates it.
     *
     * @throws RuntimeException If the cache file cannot be removed.
     */
    public function clear(): void
    {
        if (is_file($this->cacheFile) && !@unlink($this->cacheFile)) {
            throw new RuntimeException(sprintf('Unable to remove the cache file "%s".', $this->cacheFile));
        }
    }

    /**
     * Whether no resource of the decorated loader has been modified since the generation of the cache file. A loader
     * not implementing `ResourceAwareRouteLoaderInterface` has no tracked resource, so its cache file is always fresh.
     */
    private function isFresh(): bool
    {
        if (!$this->loader instanceof ResourceAwareRouteLoaderInterface) {
            return true;
        }

        clearstatcache();

        $generatedAt = filemtime($this->cacheFile);
        if ($generatedAt === false) {
            return false;
        }

        foreach ($this->loader->getResources() as $resource) {
            if ($this->isModifiedSince($resource, $generatedAt)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Modification times have a one-second precision, so a resource modified during the second the cache file was
     * generated is considered modified: the cache file may be regenerated a few times more, but never kept stale.
     *
     * Adding or removing a file changes the modification time of its directory, so directories are checked too.
     */
    private function isModifiedSince(string $resource, int $timestamp): bool
    {
        if (!file_exists($resource) || filemtime($resource) >= $timestamp) {
            return true;
        }

        if (!is_dir($resource)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resource, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getMTime() >= $timestamp) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return RouteDefinition[]
     */
    private function generate(): array
    {
        $routeDefinitions = $this->loader->getRouteDefinitions();

        $lines = [];
        foreach ($routeDefinitions as $routeDefinition) {
            $lines[] = sprintf(
                '    new \\%s(methods: %s, path: %s, handler: %s, name: %s, priority: %s, middlewares: %s),',
                RouteDefinition::class,
                $this->export($routeDefinition->methods),
                $this->export($routeDefinition->path),
                $this->export($routeDefinition->handler),
                $this->export($routeDefinition->name),
                $this->export($routeDefinition->priority),
                $this->export($routeDefinition->middlewares)
            );
        }

        $code = "<?php declare(strict_types=1);\n\n"
            . sprintf("// Generated by %s, do not edit.\n\n", self::class)
            . "return [\n"
            . implode("\n", $lines) . ($lines !== [] ? "\n" : '')
            . "];\n";

        $this->write($code);

        return $routeDefinitions;
    }

    /**
     * @throws LogicException If the value is neither a scalar, null nor an array of them.
     */
    private function export(mixed $value): string
    {
        if ($value === null || is_scalar($value)) {
            return var_export($value, true);
        }

        if (!is_array($value)) {
            throw new LogicException(sprintf(
                'Unable to cache the route definitions: a value of type "%s" cannot be exported, only scalars, null and arrays of them can.',
                get_debug_type($value)
            ));
        }

        $items = [];
        $isList = array_is_list($value);
        foreach ($value as $key => $item) {
            $items[] = ($isList ? '' : var_export($key, true) . ' => ') . $this->export($item);
        }

        return '[' . implode(', ', $items) . ']';
    }

    /**
     * Writes the cache file atomically, so that a concurrent request never reads a partially written file.
     *
     * @throws RuntimeException If the cache file cannot be written.
     */
    private function write(string $code): void
    {
        $directory = dirname($this->cacheFile);
        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create the cache directory "%s".', $directory));
        }

        $temporaryFile = $this->cacheFile . '.' . bin2hex(random_bytes(8)) . '.tmp';
        if (@file_put_contents($temporaryFile, $code) === false) {
            throw new RuntimeException(sprintf('Unable to write the cache file "%s".', $this->cacheFile));
        }

        if (!@rename($temporaryFile, $this->cacheFile)) {
            @unlink($temporaryFile);

            throw new RuntimeException(sprintf('Unable to write the cache file "%s".', $this->cacheFile));
        }

        $this->invalidateOpcache();
    }

    /**
     * OPcache may keep serving the previous version of the cache file, especially with `opcache.validate_timestamps=0`.
     */
    private function invalidateOpcache(): void
    {
        if (function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOL)) {
            @opcache_invalidate($this->cacheFile, true);
        }
    }
}
