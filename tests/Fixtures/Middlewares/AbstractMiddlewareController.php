<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Middlewares;

use Routing\Attribute\{Get, Middleware};

/**
 * Not a controller: its class middlewares are ignored, its method middlewares are inherited.
 */
#[Middleware('App\\Middleware\\IgnoredFromAbstractClass')]
abstract class AbstractMiddlewareController
{

    #[Get('/inherited', name: 'inherited')]
    #[Middleware('App\\Middleware\\FromAbstractMethod')]
    public function inherited(): void {}
}
