<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Order;

use Routing\Attribute\{AsController, Get, Post};

/**
 * Methods are declared in a non-alphabetical order, and the methods of the parent class and of the trait are named so
 * that sorting them by name interleaves them, whatever the order in which reflection returns them.
 */
#[AsController('/order')]
class OrderController extends AbstractOrderController
{

    use OrderTrait;

    #[Get('/own-z')]
    public function ownZ(): void {}

    #[Post('/own-a')]
    #[Get('/own-a')]
    public function ownA(): void {}
}
