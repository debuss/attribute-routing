<?php

namespace Routing\Attribute;

use Attribute;

/**
 * An alias of the `Controller` attribute, dedicated for the base controllers of the application.
 *
 * @see Controller
 */
#[Attribute(Attribute::TARGET_CLASS)]
class BaseController
{

    public function __construct(
        public string $prefix = ''
    ) {}
}
