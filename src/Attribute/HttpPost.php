<?php

namespace Routing\Attribute;

use Attribute;

/**
 * Identifies a method that supports the HTTP POST method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class HttpPost extends HttpMethod
{

    public function __construct(string $path, string $name = '', int $priority = 0)
    {
        parent::__construct(['POST'], $path);

        $this->name = $name;
        $this->priority = $priority;
    }
}
