<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures;

use Attribute;
use Routing\Attribute\AsController;

/**
 * A custom controller attribute, extending `AsController` with a predefined prefix.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AsAdminController extends AsController
{

    public function __construct(int $priority = 0)
    {
        parent::__construct('/admin', $priority);
    }
}
