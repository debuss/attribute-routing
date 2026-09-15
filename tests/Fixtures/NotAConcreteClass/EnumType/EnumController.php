<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\NotAConcreteClass\EnumType;

use Routing\Attribute\{AsController, Get};

#[AsController]
enum EnumController: string
{

    case Foo = 'foo';

    #[Get('/enum')]
    public function index(): void {}
}
