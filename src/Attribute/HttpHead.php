<?php

namespace Routing\Attribute;

use Attribute;

/**
 * Identifies a method that supports the HTTP HEAD method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class HttpHead extends HttpMethod
{

    public function __construct(string $path, string $name = '', int $priority = 0)
    {
        parent::__construct(['HEAD'], $path);

        $this->name = $name;
        $this->priority = $priority;
    }
}
