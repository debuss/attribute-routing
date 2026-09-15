<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\NonPublic\ProtectedMethod;

use Routing\Attribute\{AsController, Get};

#[AsController]
class ProtectedRouteController
{

    #[Get('/protected')]
    protected function hidden(): void {}
}
