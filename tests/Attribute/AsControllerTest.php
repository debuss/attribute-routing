<?php declare(strict_types=1);

namespace Routing\Tests\Attribute;

use Attribute;
use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Routing\Attribute\AsController;
use Routing\Tests\Fixtures\AsAdminController;

#[CoversClass(AsController::class)]
final class AsControllerTest extends TestCase
{

    public function testPrefixAndPriorityHaveDefaultValues(): void
    {
        $attribute = new AsController();

        $this->assertSame('', $attribute->prefix);
        $this->assertSame(0, $attribute->priority);
    }

    public function testItAcceptsPrefixAndPriority(): void
    {
        $attribute = new AsController(prefix: '/api', priority: 7);

        $this->assertSame('/api', $attribute->prefix);
        $this->assertSame(7, $attribute->priority);
    }

    public function testItIsANonRepeatableClassAttribute(): void
    {
        $attributes = (new ReflectionClass(AsController::class))->getAttributes(Attribute::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(Attribute::TARGET_CLASS, $attributes[0]->newInstance()->flags);
    }

    public function testItCanBeExtendedToCreateCustomControllerAttributes(): void
    {
        $attribute = new AsAdminController(priority: 3);

        $this->assertSame('/admin', $attribute->prefix);
        $this->assertSame(3, $attribute->priority);
    }

    public function testItIsReadonly(): void
    {
        $attribute = new AsController();

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cannot modify readonly property');

        // @phpstan-ignore property.readOnlyAssignOutOfClass (intentional, checks the property is readonly)
        $attribute->prefix = '/other';
    }
}
