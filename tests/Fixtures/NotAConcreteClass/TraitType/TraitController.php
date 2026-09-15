<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\NotAConcreteClass\TraitType;

use Routing\Attribute\{AsController, Get};

// @phpstan-ignore trait.unused (intentional, a trait with a controller attribute)
#[AsController]
trait TraitController
{

    #[Get('/trait')]
    public function index(): void {}
}
