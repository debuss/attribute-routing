<?php declare(strict_types=1);

namespace Routing\Attribute;

use Attribute;
use InvalidArgumentException;
use function array_map, array_values, count, is_string, strtoupper;

/**
 * Identifies a method that supports a given set of HTTP methods.
 *
 * This attribute can be extended to create your own route attributes, like `Get`, `Post`, ...
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Route
{

    /** @var string[] */
    public readonly array $methods;

    /**
     * @param string|string[] $methods One or several HTTP methods, case-insensitive.
     * @throws InvalidArgumentException If no HTTP method is given.
     */
    public function __construct(
        string|array $methods,
        public readonly string $path = '',
        public readonly string $name = '',
        public readonly int $priority = 0
    ) {
        $methods = is_string($methods) ? [$methods] : array_values($methods);

        if (count($methods) === 0) {
            throw new InvalidArgumentException('A route must define at least one HTTP method.');
        }

        $this->methods = array_map(strtoupper(...), $methods);
    }
}
