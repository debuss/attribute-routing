# Attribute Routing

A framework-agnostic PHP library that discovers and loads route definitions from PHP 8 attributes.  
It scans your controller classes, reads `#[Controller]` / `#[ApiController]` and HTTP-method attributes, and returns plain `RouteDefinition` objects that you can feed into any router (FastRoute, Aura Router, Symfony Router, …).

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

| Class / Attribute                        | Target | Description                                                                                                |
|------------------------------------------|---|------------------------------------------------------------------------------------------------------------|
| `#[Controller(prefix: '')]`              | Class | Marks a class as a controller. An optional `prefix` is prepended to every route path defined in the class. |
| `#[BaseController(prefix: '')]`          | Class | Alias of `#[Controller]`, dedicated to base controllers.                                                   |
| `#[ApiController(prefix: '')]`           | Class | Alias of `#[Controller]`, dedicated to API controllers.                                                    |
| `#[HttpGet(path, name?, priority?)]`     | Method | Maps the method to `GET` requests.                                                                         |
| `#[HttpPost(path, name?, priority?)]`    | Method | Maps the method to `POST` requests.                                                                        |
| `#[HttpPut(path, name?, priority?)]`     | Method | Maps the method to `PUT` requests.                                                                         |
| `#[HttpPatch(path, name?, priority?)]`   | Method | Maps the method to `PATCH` requests.                                                                       |
| `#[HttpDelete(path, name?, priority?)]`  | Method | Maps the method to `DELETE` requests.                                                                      |
| `#[HttpHead(path, name?, priority?)]`    | Method | Maps the method to `HEAD` requests.                                                                        |
| `#[HttpOptions(path, name?, priority?)]` | Method | Maps the method to `OPTIONS` requests.                                                                     |
| `AttributeRouteLoader`                   | — | Scans a directory for controllers and returns `RouteDefinition[]`.                                         |
| `RouteDefinition`                        | — | Value object holding `methods`, `path`, `handler`, `name`, and `priority`.                                 |

---

## Usage

### 1. Annotate your controllers

```php
<?php

namespace App\Controller;

use Routing\Attribute\Controller;
use Routing\Attribute\HttpGet;
use Routing\Attribute\HttpPost;
use Routing\Attribute\HttpDelete;

#[Controller(prefix: '/users')]
class UserController
{
    #[HttpGet('/', name: 'users.index')]
    public function index(): void { /* … */ }

    #[HttpGet('/{id}', name: 'users.show')]
    public function show(int $id): void { /* … */ }

    #[HttpPost('/', name: 'users.store')]
    public function store(): void { /* … */ }

    #[HttpDelete('/{id}', name: 'users.destroy')]
    public function destroy(int $id): void { /* … */ }
}
```

You can also use `#[ApiController]` as a drop-in replacement for `#[Controller]`:

```php
use Routing\Attribute\ApiController;

#[ApiController(prefix: '/api/v1/products')]
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

## Route path building

The final path is built by concatenating the controller prefix and the method path, normalising slashes automatically:

| Controller prefix | Method path | Final path |
|---|---|---|
| *(none)* | `/users` | `/users` |
| `/users` | `/` | `/users` |
| `/users` | `/{id}` | `/users/{id}` |
| `api/v1` | `products` | `/api/v1/products` |

---

## HTTP method attributes reference

All HTTP-method attributes share the same signature:

```php
#[HttpGet(
    path: '/your/path',   // required
    name: 'route.name',   // optional, default ''
    priority: 0           // optional, default 0
)]
```

| Attribute | HTTP verb |
|---|---|
| `#[HttpGet]` | GET |
| `#[HttpPost]` | POST |
| `#[HttpPut]` | PUT |
| `#[HttpPatch]` | PATCH |
| `#[HttpDelete]` | DELETE |
| `#[HttpHead]` | HEAD |
| `#[HttpOptions]` | OPTIONS |

---

## License

MIT © Alexandre Debusschère
