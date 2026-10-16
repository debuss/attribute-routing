<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures;

use Attribute;
use Routing\Attribute\Middleware;

/**
 * A custom middleware attribute, extending `Middleware` with a predefined middleware.
 */
#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Authenticated extends Middleware
{

    public function __construct()
    {
        parent::__construct('App\\Middleware\\Authenticate');
    }
}
