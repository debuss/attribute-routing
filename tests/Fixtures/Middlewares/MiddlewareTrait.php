<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Middlewares;

use Routing\Attribute\{Get, Middleware};

/**
 * Not a controller: its method middlewares are inherited by the controllers using it.
 */
trait MiddlewareTrait
{

    #[Get('/from-trait', name: 'from-trait')]
    #[Middleware('App\\Middleware\\FromTraitMethod')]
    public function fromTrait(): void {}
}
