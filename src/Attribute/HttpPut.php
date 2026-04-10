<?php

namespace Routing\Attribute;

use Attribute;

/**
 * Identifies a method that supports the HTTP PUT method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class HttpPut extends HttpMethod
{

    public function __construct(string $path, string $name = '', int $priority = 0)
    {
        parent::__construct(['PUT'], $path);

        $this->name = $name;
        $this->priority = $priority;
    }
}
