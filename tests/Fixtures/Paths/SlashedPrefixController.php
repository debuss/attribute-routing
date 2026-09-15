<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Paths;

use Routing\Attribute\{AsController, Get};

#[AsController(prefix: '/users')]
class SlashedPrefixController
{

    #[Get('/', name: 'slashed-prefix.root')]
    #[Get('', name: 'slashed-prefix.empty')]
    #[Get(name: 'slashed-prefix.omitted')]
    #[Get('/{id}', name: 'slashed-prefix.param')]
    public function index(): void {}
}
