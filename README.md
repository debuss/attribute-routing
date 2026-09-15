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
| `AttributeRouteLoader`                       | —      | Scans a directory for controllers and returns `RouteDefinition[]`.                                         |
| `RouteDefinition`                            | —      | Value object holding `methods`, `path`, `handler`, `name`, and `priority`.                                 |

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

## Discovery rules

`AttributeRouteLoader` scans every `.php` file of the given directory (recursively) and maps it to a class name using the PSR-4 convention. The classes must be autoloadable.

- Only classes marked with `#[AsController]`, or an attribute extending it, are considered controllers. Other classes are ignored, even if they have route attributes.
- Only concrete classes can be controllers: a `LogicException` is thrown if `#[AsController]` is set on an interface, a trait, an enum or an abstract class.
- PHP attributes are not inherited, so each concrete controller must have its own `#[AsController]`. Routes defined in an abstract parent class or in a trait are inherited by the concrete controllers extending or using them, with the prefix of the concrete controller.
- Route attributes must be set on **public** methods, otherwise a `LogicException` is thrown.
- An `InvalidArgumentException` is thrown if the given path is not an existing directory.

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

Priorities can be negative. The order of routes sharing the same priority is not guaranteed: use priorities when the registration order matters.

---

## Route attributes reference

`#[Route]` maps a method to one or several HTTP methods (case-insensitive, normalized to uppercase):

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
