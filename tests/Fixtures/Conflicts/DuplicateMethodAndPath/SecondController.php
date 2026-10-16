<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Conflicts\DuplicateMethodAndPath;

use Routing\Attribute\{AsController, Route};

#[AsController('/same')]
class SecondController
{

    #[Route(['POST', 'GET'])]
    public function index(): void {}
}
