<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Basic\Api;

use Routing\Attribute\{AsController, Get, Head, Options, Patch, Put};

#[AsController(prefix: 'api/v1/products')]
class ProductController
{

    #[Get('/{id}', name: 'products.show')]
    #[Head('/{id}', name: 'products.head')]
    public function show(int $id): void {}

    #[Put('/{id}')]
    #[Patch('/{id}')]
    public function update(int $id): void {}

    #[Options('/')]
    public function options(): void {}
}
