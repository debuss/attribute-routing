<?php declare(strict_types=1);

namespace Routing\Tests;

use LogicException;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use Routing\{RouteConflictChecker, RouteDefinition};
use stdClass;
use function sprintf;

#[CoversClass(RouteConflictChecker::class)]
final class RouteConflictCheckerTest extends TestCase
{

    public function testRoutesThatDoNotConflictPass(): void
    {
        $this->expectNotToPerformAssertions();

        (new RouteConflictChecker())->check([
            new RouteDefinition(['GET'], '/users', 'index', 'users.index'),
            new RouteDefinition(['POST'], '/users', 'store', 'users.store'),
            new RouteDefinition(['GET', 'HEAD'], '/users/{id}', 'show'),
            new RouteDefinition(['DELETE'], '/users/{id}', 'destroy'),
            new RouteDefinition(['GET'], '/users/{slug}', 'showBySlug'),
        ]);
    }

    public function testAnEmptyListOfRoutesPasses(): void
    {
        $this->expectNotToPerformAssertions();

        (new RouteConflictChecker())->check([]);
    }

    public function testTwoRoutesWithTheSameNameThrowAnException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The route name "users.index" is used by both "index" and "list".');

        (new RouteConflictChecker())->check([
            new RouteDefinition(['GET'], '/users', 'index', 'users.index'),
            new RouteDefinition(['GET'], '/users/list', 'list', 'users.index'),
        ]);
    }

    public function testTwoRoutesWithTheSameHttpMethodAndPathThrowAnException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The route "POST /users" is defined by both "store" and "create".');

        (new RouteConflictChecker())->check([
            new RouteDefinition(['GET', 'POST'], '/users', 'store'),
            new RouteDefinition(['PUT', 'POST'], '/users', 'create'),
        ]);
    }

    public function testAnExplicitNameEqualToAGeneratedNameThrowsAnException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The route name "/users^GET" is used by both "index" and "other".');

        (new RouteConflictChecker())->check([
            new RouteDefinition(['GET'], '/users', 'index'),
            new RouteDefinition(['GET'], '/other', 'other', '/users^GET'),
        ]);
    }

    public function testTheHttpMethodAndPathConflictIsReportedBeforeTheNameConflict(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The route "GET /users" is defined by both "index" and "list".');

        (new RouteConflictChecker())->check([
            new RouteDefinition(['GET'], '/users', 'index', 'users'),
            new RouteDefinition(['GET'], '/users', 'list', 'users'),
        ]);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function handlerProvider(): iterable
    {
        yield 'callable array of class name and method name' => [['App\\UserController', 'index'], 'App\\UserController::index()'];
        yield 'callable array of object and method name' => [[new stdClass(), 'index'], 'stdClass::index()'];
        yield 'string' => ['App\\UserController::index', 'App\\UserController::index'];
        yield 'closure' => [static fn() => null, 'Closure'];
        yield 'null' => [null, 'null'];
        yield 'integer' => [42, 'int'];
        yield 'array that is not a callable' => [['App\\UserController', 'index', 'extra'], 'array'];
        yield 'associative array' => [['class' => 'App\\UserController', 'method' => 'index'], 'array'];
    }

    #[DataProvider('handlerProvider')]
    public function testHandlersAreDescribedInTheExceptionMessage(mixed $handler, string $description): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The route "GET /users" is defined by both "%s" and "%s".', $description, $description));

        (new RouteConflictChecker())->check([
            new RouteDefinition(['GET'], '/users', $handler),
            new RouteDefinition(['GET'], '/users', $handler),
        ]);
    }
}
