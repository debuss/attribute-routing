<?php declare(strict_types=1);

namespace Routing\Attribute;

use Attribute;
use InvalidArgumentException;
use function array_values, count, in_array;

/**
 * Adds one or several middlewares to the routes of a controller (on the class) or of a method.
 *
 * Middlewares are identifiers, usually class names, that are not instantiated nor validated: it is up to your
 * application to resolve them, for instance with a container, when dispatching the route.
 *
 * This attribute can be extended to create your own middleware attributes, like `#[Authenticated]`.
 */
#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Middleware
{

    /** @var string[] */
    public readonly array $middlewares;

    /**
     * @throws InvalidArgumentException If no middleware, or an empty one, is given.
     */
    public function __construct(string ...$middlewares)
    {
        if (count($middlewares) === 0) {
            throw new InvalidArgumentException('A middleware attribute must define at least one middleware.');
        }

        if (in_array('', $middlewares, true)) {
            throw new InvalidArgumentException('A middleware cannot be an empty string.');
        }

        $this->middlewares = array_values($middlewares);
    }
}
