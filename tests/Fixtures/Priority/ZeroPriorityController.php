<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Priority;

use Routing\Attribute\{AsController, Get};

#[AsController]
class ZeroPriorityController
{

    #[Get('/zero', name: 'zero')]
    public function index(): void {}
}
