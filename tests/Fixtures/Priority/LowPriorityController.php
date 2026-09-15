<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Priority;

use Routing\Attribute\{AsController, Get};

#[AsController]
class LowPriorityController
{

    #[Get('/low/default', name: 'low.default')]
    public function default(): void {}

    #[Get('/low/boosted', name: 'low.boosted', priority: 1)]
    public function boosted(): void {}
}
