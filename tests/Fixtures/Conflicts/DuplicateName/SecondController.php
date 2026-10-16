<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Conflicts\DuplicateName;

use Routing\Attribute\{AsController, Get};

#[AsController('/second')]
class SecondController
{

    #[Get(name: 'duplicate')]
    public function index(): void {}
}
