<?php

namespace Routing\Attribute;

use Attribute;

/**
 * Identifies a method that supports the HTTP DELETE method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class HttpDelete extends HttpMethod
{

    public function __construct(string $path, string $name = '', int $priority = 0)
    {
        parent::__construct(['DELETE'], $path);

        $this->name = $name;
        $this->priority = $priority;
    }
}
