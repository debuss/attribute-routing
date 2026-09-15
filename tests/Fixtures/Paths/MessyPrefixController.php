<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Paths;

use Routing\Attribute\{AsController, Get};

#[AsController(prefix: '//shop//catalog/')]
class MessyPrefixController
{

    #[Get('//items//{id}/', name: 'messy-prefix.item')]
    public function index(): void {}
}
