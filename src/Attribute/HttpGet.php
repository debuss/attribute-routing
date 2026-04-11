<?php

namespace Routing\Attribute;

use Attribute;

/**
 * Identifies a method that supports the HTTP GET method.
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class HttpGet extends HttpMethod
{

    public function __construct(string $path, string $name = '', int $priority = 0)
    {
        parent::__construct(['GET'], $path);

        $this->name = $name;
        $this->priority = $priority;
    }
}
