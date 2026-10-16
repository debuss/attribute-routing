<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Conflicts\DuplicateAfterNormalization;

use Routing\Attribute\{AsController, Get};

#[AsController('/users')]
class UserController
{

    #[Get('/')]
    public function index(): void {}

    #[Get('')]
    public function list(): void {}
}
