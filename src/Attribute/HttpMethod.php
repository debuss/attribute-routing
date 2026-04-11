<?php

namespace Routing\Attribute;

use Attribute;

/**
 * Identifies a method that supports a given set of HTTP methods.
 */
abstract class HttpMethod
{

    public array $methods;
    public string $path = '';
    public string $name = '';
    public int $priority = 0;

    public function __construct(array $methods, string $path)
    {
        $this->methods = $methods;
        $this->path = $path;
    }
}
