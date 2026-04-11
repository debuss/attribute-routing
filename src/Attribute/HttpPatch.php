<?php

namespace Routing\Attribute;

use Attribute;

/**
 * Identifies a method that supports the HTTP PATCH method.
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class HttpPatch extends HttpMethod
{

    public function __construct(string $path, string $name = '', int $priority = 0)
    {
        parent::__construct(['PATCH'], $path);

        $this->name = $name;
        $this->priority = $priority;
    }
}
