<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Basic;

use Routing\Attribute\Get;

/**
 * Has route attributes but no `#[AsController]` attribute: must be ignored.
 */
class NotAController
{

    #[Get('/not-a-controller')]
    public function index(): void {}
}
