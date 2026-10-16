<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Middlewares;

use Routing\Attribute\{AsController, Get, Middleware};

#[AsController('/no-middleware')]
class NoMiddlewareController
{

    #[Get(name: 'no-middleware')]
    public function index(): void {}

    #[Get('/method-only', name: 'method-only')]
    #[Middleware('App\\Middleware\\MethodOnly')]
    public function methodOnly(): void {}
}
