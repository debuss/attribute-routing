<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Paths;

use Routing\Attribute\{AsController, Get, Post, Put};

/**
 * Each path variant uses its own HTTP method, otherwise the routes would conflict.
 */
#[AsController]
class NoPrefixController
{

    #[Get('/users', name: 'no-prefix.leading-slash')]
    #[Post('users/', name: 'no-prefix.trailing-slash')]
    #[Get('/', name: 'no-prefix.root')]
    #[Post('', name: 'no-prefix.empty')]
    #[Put(name: 'no-prefix.omitted')]
    public function index(): void {}
}
