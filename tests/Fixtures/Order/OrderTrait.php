<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Order;

use Routing\Attribute\Get;

trait OrderTrait
{

    #[Get('/trait-z')]
    public function traitZ(): void {}

    #[Get('/trait-a')]
    public function traitA(): void {}
}
