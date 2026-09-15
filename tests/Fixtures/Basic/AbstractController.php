<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Basic;

use Routing\Attribute\Get;

/**
 * Not a controller: its routes are inherited by the concrete controllers extending it.
 */
abstract class AbstractController
{

    #[Get('/inherited', name: 'inherited')]
    public function inherited(): void {}
}
