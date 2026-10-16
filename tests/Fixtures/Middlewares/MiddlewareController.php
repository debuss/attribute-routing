<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Middlewares;

use Routing\Attribute\{AsController, Get, Middleware, Post};
use Routing\Tests\Fixtures\Authenticated;

#[AsController('/middlewares')]
#[Middleware('App\\Middleware\\First', 'App\\Middleware\\Second')]
#[Authenticated]
#[Middleware('App\\Middleware\\Third')]
class MiddlewareController extends AbstractMiddlewareController
{

    use MiddlewareTrait;

    #[Get('/controller-only', name: 'controller-only')]
    public function controllerOnly(): void {}

    #[Get('/with-method', name: 'with-method')]
    #[Middleware('App\\Middleware\\MethodFirst')]
    #[Middleware('App\\Middleware\\MethodSecond', 'App\\Middleware\\Third')]
    public function withMethod(): void {}

    #[Get('/shared', name: 'shared.get')]
    #[Post('/shared', name: 'shared.post')]
    #[Middleware('App\\Middleware\\Shared')]
    public function shared(): void {}

    #[Middleware('App\\Middleware\\WithoutRoute')]
    public function withoutRoute(): void {}
}
