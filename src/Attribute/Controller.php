<?php

namespace Routing\Attribute;

use Attribute;

/**
 * Indicate that the class is to be considered as a controller by the controller discovery mechanism.
 *
 * A prefix `$prefix` can be defined and will be used for every route defined in the controller.
 * For example, if the prefix is `api` and a route is defined as `/users`, the final route will be `/api/users`.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Controller
{

    public function __construct(
        public string $prefix = '',
        public int $priority = 0
    ) {}
}
