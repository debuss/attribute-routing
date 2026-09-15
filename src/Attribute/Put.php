<?php declare(strict_types=1);

namespace Routing\Attribute;

use Attribute;

/**
 * Identifies a method that supports the HTTP PUT method.
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Put extends Route
{

    public function __construct(string $path = '', string $name = '', int $priority = 0)
    {
        parent::__construct('PUT', $path, $name, $priority);
    }
}
