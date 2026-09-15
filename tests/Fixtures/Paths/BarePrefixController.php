<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Paths;

use Routing\Attribute\{AsController, Get};

#[AsController(prefix: 'api/v1')]
class BarePrefixController
{

    #[Get('products', name: 'bare-prefix.products')]
    public function index(): void {}
}
