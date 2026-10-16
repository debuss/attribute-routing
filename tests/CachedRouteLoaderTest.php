<?php declare(strict_types=1);

namespace Routing\Tests;

use LogicException;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use Routing\{AttributeRouteLoader, CachedRouteLoader, RouteDefinition};
use Routing\Tests\Fixtures\RouteLoaderStub;
use RuntimeException;
use stdClass;
use function bin2hex, chmod, file_put_contents, function_exists, glob, ini_set, is_dir, is_writable, mkdir, opcache_get_status, random_bytes, rmdir, scandir, sys_get_temp_dir, unlink;

#[CoversClass(CachedRouteLoader::class)]
final class CachedRouteLoaderTest extends TestCase
{

    private string $cacheDirectory;
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/attribute-routing-' . bin2hex(random_bytes(8));
        $this->cacheFile = $this->cacheDirectory . '/routes.php';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDirectory)) {
            chmod($this->cacheDirectory, 0777);
        }

        $this->removeDirectory($this->cacheDirectory);
    }

    public function testItGeneratesTheCacheFileOnTheFirstCall(): void
    {
        $routes = [new RouteDefinition(['GET'], '/users', ['App\\UserController', 'index'], 'users.index', 3)];
        $loader = new CachedRouteLoader(new RouteLoaderStub($routes), $this->cacheFile);

        $this->assertFileDoesNotExist($this->cacheFile);
        $this->assertSame($routes, $loader->getRouteDefinitions());
        $this->assertFileExists($this->cacheFile);
    }

    public function testItReadsTheCacheFileInsteadOfCallingTheDecoratedLoader(): void
    {
        $routes = [new RouteDefinition(['GET'], '/users', ['App\\UserController', 'index'], 'users.index', 3)];
        $stub = new RouteLoaderStub($routes);

        (new CachedRouteLoader($stub, $this->cacheFile))->getRouteDefinitions();
        $cachedRoutes = (new CachedRouteLoader($stub, $this->cacheFile))->getRouteDefinitions();

        $this->assertSame(1, $stub->calls);
        $this->assertEquals($routes, $cachedRoutes);
    }

    public function testCachedRoutesAreEqualToTheRoutesOfTheAttributeRouteLoader(): void
    {
        $attributeLoader = new AttributeRouteLoader('Routing\\Tests\\Fixtures\\Basic', __DIR__ . '/Fixtures/Basic');
        $expected = $attributeLoader->getRouteDefinitions();

        (new CachedRouteLoader($attributeLoader, $this->cacheFile))->warmUp();

        $this->assertEquals($expected, $this->readCache());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function handlerProvider(): iterable
    {
        yield 'callable array' => [['App\\UserController', 'index']];
        yield 'string' => ['App\\UserController::index'];
        yield 'associative array' => [['controller' => 'App\\UserController', 'action' => 'index']];
        yield 'nested array' => [['App\\UserController', ['index', 'show']]];
        yield 'integer' => [42];
        yield 'float' => [4.2];
        yield 'boolean' => [false];
        yield 'null' => [null];
        yield 'special characters' => ["It's a \\ back\\slash, a \$variable, a \"quote\" and a ?> tag\n"];
    }

    #[DataProvider('handlerProvider')]
    public function testHandlersAreCachedAsIs(mixed $handler): void
    {
        $routes = [new RouteDefinition(['GET'], '/users', $handler)];

        (new CachedRouteLoader(new RouteLoaderStub($routes), $this->cacheFile))->warmUp();

        $this->assertSame($handler, $this->readCache()[0]->handler);
    }

    public function testAnEmptyListOfRoutesIsCached(): void
    {
        $stub = new RouteLoaderStub();

        (new CachedRouteLoader($stub, $this->cacheFile))->getRouteDefinitions();

        $this->assertSame([], (new CachedRouteLoader($stub, $this->cacheFile))->getRouteDefinitions());
        $this->assertSame(1, $stub->calls);
    }

    public function testWarmUpRegeneratesAnExistingCacheFile(): void
    {
        $stub = new RouteLoaderStub([new RouteDefinition(['GET'], '/old', 'old')]);
        $loader = new CachedRouteLoader($stub, $this->cacheFile);
        $loader->getRouteDefinitions();

        $stub->routes = [new RouteDefinition(['GET'], '/new', 'new')];
        $loader->warmUp();

        $this->assertSame('/new', $loader->getRouteDefinitions()[0]->path);
        $this->assertSame(2, $stub->calls);
    }

    public function testWarmUpInvalidatesTheOpcacheOfTheCacheFile(): void
    {
        if (!function_exists('opcache_get_status') || opcache_get_status() === false) {
            $this->markTestSkipped('OPcache is not enabled.');
        }

        // Mimics a production configuration, where OPcache never checks whether a cached file has changed
        $previousIniValues = [
            'opcache.validate_timestamps' => ini_set('opcache.validate_timestamps', '0'),
            'opcache.file_update_protection' => ini_set('opcache.file_update_protection', '0'),
        ];

        try {
            $stub = new RouteLoaderStub([new RouteDefinition(['GET'], '/old', 'old')]);
            $loader = new CachedRouteLoader($stub, $this->cacheFile);
            $loader->warmUp();
            $this->assertSame('/old', $loader->getRouteDefinitions()[0]->path);

            $stub->routes = [new RouteDefinition(['GET'], '/new', 'new')];
            $loader->warmUp();

            $this->assertSame('/new', $loader->getRouteDefinitions()[0]->path);
        } finally {
            foreach ($previousIniValues as $name => $value) {
                if ($value !== false) {
                    ini_set($name, $value);
                }
            }
        }
    }

    public function testClearRemovesTheCacheFileSoThatItIsRegeneratedOnTheNextCall(): void
    {
        $stub = new RouteLoaderStub([new RouteDefinition(['GET'], '/old', 'old')]);
        $loader = new CachedRouteLoader($stub, $this->cacheFile);
        $loader->getRouteDefinitions();

        $loader->clear();
        $this->assertFileDoesNotExist($this->cacheFile);

        $stub->routes = [new RouteDefinition(['GET'], '/new', 'new')];
        $this->assertSame('/new', $loader->getRouteDefinitions()[0]->path);
    }

    public function testClearDoesNothingIfThereIsNoCacheFile(): void
    {
        (new CachedRouteLoader(new RouteLoaderStub(), $this->cacheFile))->clear();

        $this->assertFileDoesNotExist($this->cacheFile);
    }

    public function testItCreatesTheMissingDirectoriesOfTheCacheFile(): void
    {
        $cacheFile = $this->cacheDirectory . '/nested/directories/routes.php';

        (new CachedRouteLoader(new RouteLoaderStub(), $cacheFile))->warmUp();

        $this->assertFileExists($cacheFile);
    }

    public function testNoTemporaryFileIsLeftBehind(): void
    {
        (new CachedRouteLoader(new RouteLoaderStub(), $this->cacheFile))->warmUp();

        $this->assertSame([$this->cacheFile], glob($this->cacheDirectory . '/*'));
    }

    public function testANonExportableHandlerThrowsAnExceptionAndNoCacheFileIsWritten(): void
    {
        $routes = [new RouteDefinition(['GET'], '/users', [new stdClass(), 'index'])];
        $loader = new CachedRouteLoader(new RouteLoaderStub($routes), $this->cacheFile);

        try {
            $loader->getRouteDefinitions();
            $this->fail('An exception should have been thrown.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Unable to cache the route definitions: a value of type "stdClass" cannot be exported, only scalars, null and arrays of them can.',
                $exception->getMessage()
            );
        }

        $this->assertFileDoesNotExist($this->cacheFile);
    }

    public function testAnUncreatableCacheDirectoryThrowsAnException(): void
    {
        $this->assertTrue(@mkdir($this->cacheDirectory));
        file_put_contents($this->cacheDirectory . '/file', '');
        $cacheFile = $this->cacheDirectory . '/file/routes.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to create the cache directory "' . $this->cacheDirectory . '/file".');

        (new CachedRouteLoader(new RouteLoaderStub(), $cacheFile))->warmUp();
    }

    public function testAnUnwritableCacheFileThrowsAnExceptionAndNoTemporaryFileIsLeftBehind(): void
    {
        // rename() cannot replace a non-empty directory
        $this->assertTrue(@mkdir($this->cacheFile, 0777, true));
        file_put_contents($this->cacheFile . '/file', '');

        try {
            (new CachedRouteLoader(new RouteLoaderStub(), $this->cacheFile))->warmUp();
            $this->fail('An exception should have been thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Unable to write the cache file "' . $this->cacheFile . '".', $exception->getMessage());
        }

        $this->assertSame([$this->cacheFile], glob($this->cacheDirectory . '/*'));
    }

    public function testAnUnremovableCacheFileThrowsAnException(): void
    {
        $loader = new CachedRouteLoader(new RouteLoaderStub(), $this->cacheFile);
        $loader->warmUp();
        chmod($this->cacheDirectory, 0555);

        if (is_writable($this->cacheDirectory)) {
            $this->markTestSkipped('The cache directory is still writable (running as root?).');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to remove the cache file "' . $this->cacheFile . '".');

        $loader->clear();
    }

    /**
     * Reads the cache file directly, to make sure the assertions are made against the cached routes.
     *
     * @return RouteDefinition[]
     */
    private function readCache(): array
    {
        $this->assertFileExists($this->cacheFile);

        /** @var RouteDefinition[] $routes */
        $routes = require $this->cacheFile;
        $this->assertContainsOnlyInstancesOf(RouteDefinition::class, $routes);

        return $routes;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
