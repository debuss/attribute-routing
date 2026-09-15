<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\NotAConcreteClass\InterfaceType;

use Routing\Attribute\{AsController, Get};

#[AsController]
interface InterfaceController
{

    #[Get('/interface')]
    public function index(): void;
}
