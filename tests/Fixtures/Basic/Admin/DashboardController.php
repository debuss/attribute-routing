<?php declare(strict_types=1);

namespace Routing\Tests\Fixtures\Basic\Admin;

use Routing\Attribute\Get;
use Routing\Tests\Fixtures\AsAdminController;

#[AsAdminController]
class DashboardController
{

    #[Get('/dashboard', name: 'admin.dashboard')]
    public function index(): void {}

    #[Get('/health')]
    public static function health(): void {}
}
