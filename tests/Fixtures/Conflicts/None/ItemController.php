<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Conflicts\None;

use Routing\Attribute\{AsController, Get, Head, Post, Route};

#[AsController('/items')]
class ItemController
{

    #[Get]
    #[Head]
    #[Post]
    public function index(): void {}

    #[Route(['GET', 'get'], '/deduplicated')]
    public function deduplicated(): void {}

    #[Get('/{id}')]
    public function show(): void {}

    // Different parameter names: not detected as a conflict, the router decides
    #[Get('/{slug}')]
    public function showBySlug(): void {}
}
