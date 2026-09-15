<?php declare(strict_types=1);

namespace Routing\Attribute;

use Attribute;

/**
 * Indicate that the class is to be considered as a controller by the controller discovery mechanism.
 *
 * A prefix `$prefix` can be defined and will be used for every route defined in the controller.
 * For example, if the prefix is `api` and a route is defined as `/users`, the final route will be `/api/users`.
 *
 * This attribute can be extended to create your own controller attributes, for instance with a predefined prefix.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AsController
{

    public function __construct(
        public readonly string $prefix = '',
        public readonly int $priority = 0
    ) {}
}
