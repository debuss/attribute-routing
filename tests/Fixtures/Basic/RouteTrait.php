<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Basic;

use Routing\Attribute\Get;

/**
 * Not a controller: its routes are inherited by the controllers using it.
 */
trait RouteTrait
{

    #[Get('/from-trait', name: 'from-trait')]
    public function fromTrait(): void {}
}
