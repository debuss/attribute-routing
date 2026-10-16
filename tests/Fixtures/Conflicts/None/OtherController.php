<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Conflicts\None;

use Routing\Attribute\{AsController, Get};

#[AsController('/other')]
class OtherController
{

    // Routes without name never conflict
    #[Get]
    public function index(): void {}
}
