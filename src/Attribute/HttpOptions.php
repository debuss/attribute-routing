<?php

namespace Routing\Attribute;

use Attribute;

/**
 * Identifies a method that supports the HTTP OPTIONS method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class HttpOptions extends HttpMethod
{

    public function __construct(string $path, string $name = '', int $priority = 0)
    {
        parent::__construct(['OPTIONS'], $path);

        $this->name = $name;
        $this->priority = $priority;
    }
}
