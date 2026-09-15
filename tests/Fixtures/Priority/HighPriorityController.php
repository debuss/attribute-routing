<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Priority;

use Routing\Attribute\{AsController, Get};

#[AsController(priority: 10)]
class HighPriorityController
{

    #[Get('/high/boosted', name: 'high.boosted', priority: 5)]
    public function boosted(): void {}

    #[Get('/high/default', name: 'high.default')]
    public function default(): void {}

    #[Get('/high/lowered', name: 'high.lowered', priority: -20)]
    public function lowered(): void {}
}
