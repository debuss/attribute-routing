<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Paths;

use Routing\Attribute\{AsController, Get};

#[AsController]
class NoPrefixController
{

    #[Get('/users', name: 'no-prefix.leading-slash')]
    #[Get('users/', name: 'no-prefix.trailing-slash')]
    #[Get('/', name: 'no-prefix.root')]
    #[Get('', name: 'no-prefix.empty')]
    #[Get(name: 'no-prefix.omitted')]
    public function index(): void {}
}
