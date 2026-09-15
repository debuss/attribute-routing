<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\NonPublic\PrivateMethod;

use Routing\Attribute\{AsController, Get};

#[AsController]
class PrivateRouteController
{

    // @phpstan-ignore method.unused (intentional, a route on a private method)
    #[Get('/private')]
    private function hidden(): void {}
}
