<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Conflicts\DuplicateMethodAndPath;

use Routing\Attribute\{AsController, Get};

#[AsController('/same')]
class FirstController
{

    #[Get]
    public function index(): void {}
}
