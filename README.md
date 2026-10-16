# Attribute Routing

A framework-agnostic PHP library that discovers and loads route definitions from PHP 8 attributes.  
It scans your controller classes, reads `#[AsController]` and HTTP-method attributes, and returns plain `RouteDefinition` objects that you can feed into any router (FastRoute, Aura Router, Symfony Router, …).

---

## Requirements

- PHP **8.3+**

---

## Installation

```bash
composer require debuss-a/attribute-routing
```

---

## Concepts

| Class / Attribute                            | Target | Description                                                                                                |
|----------------------------------------------|--------|------------------------------------------------------------------------------------------------------------|
| `#[AsController(prefix?, priority?)]`        | Class  | Marks a class as a controller. An optional `prefix` is prepended to every route path defined in the class. |
| `#[Route(methods, path?, name?, priority?)]` | Method | Maps the method to one or several HTTP methods.                                                            |
| `#[Get(path?, name?, priority?)]`            | Method | Maps the method to `GET` requests.                                                                         |
| `#[Post(path?, name?, priority?)]`           | Method | Maps the method to `POST` requests.                                                                        |
| `#[Put(path?, name?, priority?)]`            | Method | Maps the method to `PUT` requests.                                                                         |
| `#[Patch(path?, name?, priority?)]`          | Method | Maps the method to `PATCH` requests.                                                                       |
| `#[Delete(path?, name?, priority?)]`         | Method | Maps the method to `DELETE` requests.                                                                      |
| `#[Head(path?, name?, priority?)]`           | Method | Maps the method to `HEAD` requests.                                                                        |
| `#[Options(path?, name?, priority?)]`        | Method | Maps the method to `OPTIONS` requests.                                                                     |
| `#[Middleware(...middlewares)]`              | Both   | Adds middlewares to every route of the class, or to the routes of the method.                              |
| `AttributeRouteLoader`                       | —      | Scans a directory for controllers and returns `RouteDefinition[]`.                                         |
| `RouteDefinition`                            | —      | Value object holding `methods`, `path`, `handler`, `name`, `priority` and `middlewares`.                   |
| `ChainRouteLoader`                           | —      | Merges the route definitions of several loaders, for instance one per module.                              |
| `CachedRouteLoader`                          | —      | Decorates a loader to cache its route definitions in a PHP file.                                           |
| `RouteLoaderInterface`                       | —      | Implemented by every loader, exposes `getRouteDefinitions()`.                                              |

---

## Usage

### 1. Annotate your controllers

```php
<?php

namespace App\Controller;

use Routing\Attribute\AsController;
use Routing\Attribute\Delete;
use Routing\Attribute\Get;
use Routing\Attribute\Post;
use Routing\Attribute\Route;

#[AsController(prefix: '/users')]
class UserController
{
    #[Get(name: 'users.index')]
    public function index(): void { /* … */ }

    #[Get('/{id}', name: 'users.show')]
    public function show(int $id): void { /* … */ }

    #[Post('/', name: 'users.store')]
    public function store(): void { /* … */ }

    #[Delete('/{id}', name: 'users.destroy')]
    public function destroy(int $id): void { /* … */ }

    #[Route(['GET', 'POST'], '/search', name: 'users.search')]
    public function search(): void { /* … */ }
}
```

#### Custom controller attributes

`#[AsController]` can be extended to create your own controller attributes, for instance with a predefined prefix:

```php
use Attribute;
use Routing\Attribute\AsController;

#[Attribute(Attribute::TARGET_CLASS)]
class AsApiController extends AsController
{
    public function __construct(int $priority = 0)
    {
        parent::__construct('/api', $priority);
    }
}

#[AsApiController]
class ProductController { /* … */ }
```

### 2. Load route definitions

Instantiate `AttributeRouteLoader` with the **root namespace** of your controllers and the **directory path** where they live, then call `getRouteDefinitions()`.

```php
<?php

use Routing\AttributeRouteLoader;

$loader = new AttributeRouteLoader(
    namespace: 'App\\Controller',
    path: __DIR__ . '/src/Controller'
);

/** @var \Routing\RouteDefinition[] $routes */
$routes = $loader->getRouteDefinitions();
```

Each `RouteDefinition` exposes:

```php
$route->methods;   // string[]  – e.g. ['GET']
$route->path;      // string    – e.g. '/users/{id}'
$route->handler;   // array     – [ClassName::class, 'methodName']
$route->name;      // string    – route name (empty string if not set)
$route->priority;  // int       – route priority (0 by default)
$route->middlewares; // string[] – e.g. [AuthMiddleware::class] (empty array if not set)
```

### 3. Register routes in your router

The `RouteDefinition` objects are intentionally router-agnostic. Below are two examples.

#### FastRoute

```php
<?php

use FastRoute\RouteCollector;
use Routing\AttributeRouteLoader;

$loader = new AttributeRouteLoader('App\\Controller', __DIR__ . '/src/Controller');

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) use ($loader) {
    foreach ($loader->getRouteDefinitions() as $route) {
        $r->addRoute($route->methods, $route->path, $route->handler);
    }
});
```

#### Aura Router

```php
<?php

use Aura\Router\RouterContainer;
use Routing\AttributeRouteLoader;

$loader = new AttributeRouteLoader('App\\Controller', __DIR__ . '/src/Controller');
$routerContainer = new RouterContainer();
$map = $routerContainer->getMap();

foreach ($loader->getRouteDefinitions() as $route) {
    $map->route($route->name, $route->path)
        ->allows($route->methods)
        ->handler($route->handler);
}
```

---

## Loading several directories

`AttributeRouteLoader` scans a single directory, matching a single namespace. To load the controllers of several directories, like the modules of an application, combine several loaders with `ChainRouteLoader`:

```php
<?php

use Routing\AttributeRouteLoader;
use Routing\ChainRouteLoader;

$loader = new ChainRouteLoader(
    new AttributeRouteLoader('App\\Blog\\Controller', __DIR__ . '/src/Blog/Controller'),
    new AttributeRouteLoader('App\\Shop\\Controller', __DIR__ . '/src/Shop/Controller'),
);

$routes = $loader->getRouteDefinitions();
```

- The routes of all the loaders are sorted by descending priority. Routes sharing the same priority keep the order of the loaders, then the order of each loader.
- [Conflicts](#conflicts) are detected across loaders: a `LogicException` is thrown if a route of the blog has the same name, or the same HTTP method and path, as a route of the shop.
- `ChainRouteLoader` accepts any `RouteLoaderInterface`, so you can mix `AttributeRouteLoader` with your own loaders, and cache the whole chain at once with `new CachedRouteLoader(new ChainRouteLoader(...), $cacheFile)`.

---

## Caching

`AttributeRouteLoader` scans the directory, autoloads every controller and reads their attributes through reflection on each call. With a shared-nothing runtime like PHP-FPM, this happens on every request, so you should cache the route definitions in production.

`CachedRouteLoader` decorates any `RouteLoaderInterface` and caches its route definitions in a PHP file. The first call generates the file, the next ones only `require` it: the decorated loader, and therefore the scan, is not called anymore. With OPcache enabled, the file is kept in shared memory and reading it costs nearly nothing.

```php
<?php

use Routing\AttributeRouteLoader;
use Routing\CachedRouteLoader;

$loader = new AttributeRouteLoader('App\\Controller', __DIR__ . '/src/Controller');

if ($environment === 'prod') {
    $loader = new CachedRouteLoader($loader, __DIR__ . '/var/cache/routes.php');
}

$routes = $loader->getRouteDefinitions();
```

The cache file is **never invalidated automatically**, so it does not reflect the changes made to your controllers:

- do not use `CachedRouteLoader` in development,
- regenerate the cache file on each deployment, with `warmUp()`, or remove it with `clear()` so that it is regenerated by the next call.

```php
// For instance in a deployment script
(new CachedRouteLoader($loader, __DIR__ . '/var/cache/routes.php'))->warmUp();
```

Generating the cache file during the deployment also allows you to use a read-only filesystem in production.

> [!NOTE]
> `warmUp()` invalidates the OPcache entry of the cache file in the **current** process only: when it is called from the command line, PHP-FPM may keep serving the previous routes if `opcache.validate_timestamps` is disabled. Reload PHP-FPM after the deployment, as you would do for any other PHP file.

Only route handlers made of scalars, `null` and arrays of them can be cached (`AttributeRouteLoader` handlers are always `[ClassName::class, 'methodName']` arrays). A `LogicException` is thrown otherwise, and a `RuntimeException` if the cache file cannot be written.

> [!TIP]
> Some routers have their own cache, like FastRoute with `cachedDispatcher()`, which does not call the route definitions callback when its cache is fresh. In that case, you do not need `CachedRouteLoader`.

---

## Discovery rules

`AttributeRouteLoader` scans every `.php` file of the given directory (recursively) and maps it to a class name using the PSR-4 convention. The classes must be autoloadable.

- Only classes marked with `#[AsController]`, or an attribute extending it, are considered controllers. Other classes are ignored, even if they have route attributes.
- Only concrete classes can be controllers: a `LogicException` is thrown if `#[AsController]` is set on an interface, a trait, an enum or an abstract class.
- PHP attributes are not inherited, so each concrete controller must have its own `#[AsController]`. Routes defined in an abstract parent class or in a trait are inherited by the concrete controllers extending or using them, with the prefix of the concrete controller.
- Route attributes must be set on **public** methods, otherwise a `LogicException` is thrown.
- An `InvalidArgumentException` is thrown if the given path is not an existing directory.

### Conflicts

A `LogicException`, naming both handlers, is thrown if two routes (of the same loader, or of different loaders combined with `ChainRouteLoader`):

- have the same name (routes without name never conflict),
- or have the same HTTP method and the same path, once normalized: `#[Get('/')]` and `#[Get('')]` on a controller prefixed with `/users` both define `GET /users`.

Paths are compared as strings: routes that only differ by their parameter names, like `/users/{id}` and `/users/{slug}`, are not detected as conflicting, since whether they conflict depends on your router (for instance on the parameter patterns).

---

## Route path building

The final path is built by concatenating the controller prefix and the method path, normalising slashes automatically:

| Controller prefix | Method path | Final path |
|---|---|---|
| *(none)* | `/users` | `/users` |
| `/users` | *(none)* | `/users` |
| `/users` | `/` | `/users` |
| `/users` | `/{id}` | `/users/{id}` |
| `api/v1` | `products` | `/api/v1/products` |

---

## Route priority

Routes are returned sorted by **descending priority**, so that you can register the most specific routes first (useful for routers that match the first registered route, or to avoid a static route being shadowed by a dynamic one).

The priority of a route is the sum of the controller priority and the route priority, both `0` by default:

```php
#[AsController(prefix: '/users', priority: 10)]
class UserController
{
    #[Get('/me', priority: 5)]           // priority 15
    public function me(): void { /* … */ }

    #[Get('/{id}')]                      // priority 10
    public function show(string $id): void { /* … */ }

    #[Get('/{id}/legacy', priority: -20)] // priority -10
    public function legacy(string $id): void { /* … */ }
}
```

Priorities can be negative. Routes sharing the same priority are returned in a deterministic order: sorted by controller class name, then by method: first the methods declared in the controller, in their order of declaration, then the methods inherited from a parent class or coming from a trait, by name. Several route attributes on a method keep their order of declaration. The order depends neither on the filesystem nor on the PHP version, so it is the same on every machine and the cache files of `CachedRouteLoader` are reproducible. Still, use priorities when the registration order matters, rather than relying on class names.

---

## Middlewares

`#[Middleware]` adds one or several middlewares to the routes. Set on a controller, it applies to every route of the controller; set on a method, it applies to the routes of this method only. It is repeatable:

```php
use Routing\Attribute\{AsController, Get, Middleware};

#[AsController('/admin')]
#[Middleware(AuthMiddleware::class, AdminMiddleware::class)]
class DashboardController
{
    #[Get('/stats', name: 'admin.stats')]
    #[Middleware(RateLimitMiddleware::class)]
    public function stats(): void { /* … */ }
}

// $route->middlewares === [AuthMiddleware::class, AdminMiddleware::class, RateLimitMiddleware::class]
```

- The controller middlewares come first, followed by the method middlewares, each in their order of declaration. Duplicates are kept.
- Middlewares are identifiers, usually class names (`class-string`), that are **neither instantiated nor validated**: any string understood by your application works, like an alias (`'auth:api'`). This keeps the route definitions cacheable and lets your container build the middlewares with their dependencies.
- Like routes, middlewares set on a method of an abstract parent class or of a trait are inherited. Middlewares set on an abstract parent class are ignored, since PHP class attributes are not inherited.
- Middlewares set on a method without route attribute are ignored.
- An `InvalidArgumentException` is thrown if no middleware, or an empty string, is given.

Like `#[AsController]` and `#[Route]`, `#[Middleware]` can be extended to create your own middleware attributes:

```php
use Attribute;
use Routing\Attribute\Middleware;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Authenticated extends Middleware
{
    public function __construct()
    {
        parent::__construct(AuthMiddleware::class);
    }
}
```

### Registering middlewares in your router

Most routers have no notion of per-route middlewares, so you have to keep them alongside the handler and run them when dispatching the route. For instance with FastRoute, which stores any value as handler:

```php
$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) use ($loader) {
    foreach ($loader->getRouteDefinitions() as $route) {
        $r->addRoute($route->methods, $route->path, [
            'handler' => $route->handler,
            'middlewares' => $route->middlewares,
        ]);
    }
});

[$status, $routeData, $vars] = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
// $routeData['middlewares'] contains the middleware identifiers, to resolve with your container
```

With Aura Router, use the route extras:

```php
foreach ($loader->getRouteDefinitions() as $route) {
    $map->route($route->name, $route->path)
        ->allows($route->methods)
        ->handler($route->handler)
        ->extras(['middlewares' => $route->middlewares]);
}
```

---

## Route attributes reference

`#[Route]` maps a method to one or several HTTP methods (case-insensitive, normalized to uppercase, duplicates removed):

```php
#[Route(
    methods: ['GET', 'POST'], // required, a string or an array of strings
    path: '/your/path',       // optional, default '' (the controller prefix)
    name: 'route.name',       // optional, default ''
    priority: 0               // optional, default 0
)]
```

The HTTP method attributes are shortcuts of `#[Route]` for a single HTTP method, and share the same signature without `methods`:

```php
#[Get(
    path: '/your/path',   // optional, default '' (the controller prefix)
    name: 'route.name',   // optional, default ''
    priority: 0           // optional, default 0
)]
```

| Attribute | HTTP verb |
|---|---|
| `#[Get]` | GET |
| `#[Post]` | POST |
| `#[Put]` | PUT |
| `#[Patch]` | PATCH |
| `#[Delete]` | DELETE |
| `#[Head]` | HEAD |
| `#[Options]` | OPTIONS |

Like `#[AsController]`, `#[Route]` can be extended to create your own route attributes.

---

## Testing

```bash
composer install
composer analyse   # PHPStan, level max
composer test      # PHPUnit
```

---

## License

MIT © Alexandre Debusschère
