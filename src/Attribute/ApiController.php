<?php

namespace Routing\Attribute;

use Attribute;

/**
 * An alias of the `Controller` attribute, dedicated for the API controllers of the application.
 *
 * @see Controller
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ApiController extends Controller
{
}
