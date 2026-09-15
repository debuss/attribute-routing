<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\NotAConcreteClass\AbstractType;

use Routing\Attribute\{AsController, Get};

#[AsController]
abstract class AbstractController
{

    #[Get('/abstract')]
    public function index(): void {}
}
