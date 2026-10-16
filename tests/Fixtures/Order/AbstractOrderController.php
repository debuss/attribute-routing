<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Order;

use Routing\Attribute\Get;

abstract class AbstractOrderController
{

    #[Get('/parent-b')]
    public function parentB(): void {}

    #[Get('/parent-a')]
    public function parentA(): void {}
}
