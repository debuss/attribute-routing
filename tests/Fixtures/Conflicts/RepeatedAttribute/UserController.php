<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Conflicts\RepeatedAttribute;

use Routing\Attribute\{AsController, Get};

#[AsController('/users')]
class UserController
{

    #[Get('/{id}')]
    #[Get('{id}/')]
    public function show(): void {}
}
