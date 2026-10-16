<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Paths;

use Routing\Attribute\{AsController, Delete, Get, Options, Patch};

/**
 * Each path variant uses its own HTTP method, otherwise the routes would conflict (with `NoPrefixController` too).
 */
#[AsController(prefix: '/users')]
class SlashedPrefixController
{

    #[Patch('/', name: 'slashed-prefix.root')]
    #[Delete('', name: 'slashed-prefix.empty')]
    #[Options(name: 'slashed-prefix.omitted')]
    #[Get('/{id}', name: 'slashed-prefix.param')]
    public function index(): void {}
}
