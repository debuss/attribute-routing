<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Basic;

use Routing\Attribute\{AsController, Delete, Get, Post, Route};

#[AsController(prefix: '/users')]
class UserController
{

    #[Get('/', name: 'users.index')]
    public function index(): void {}

    #[Get('/{id}', name: 'users.show')]
    public function show(int $id): void {}

    #[Post('/', name: 'users.store')]
    public function store(): void {}

    #[Delete('/{id}', name: 'users.destroy')]
    public function destroy(int $id): void {}

    #[Route(['get', 'POST'], '/search', name: 'users.search')]
    public function search(): void {}

    public function notARoute(): void {}
}
