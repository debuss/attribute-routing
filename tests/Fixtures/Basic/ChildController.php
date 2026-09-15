<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Basic;

use Routing\Attribute\AsController;

#[AsController(prefix: '/child')]
class ChildController extends AbstractController
{

    use RouteTrait;
}
