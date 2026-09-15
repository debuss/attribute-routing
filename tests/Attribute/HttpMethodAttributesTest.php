<?php declare(strict_types=1);

namespace Routing\Tests\Attribute;

use Attribute;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Routing\Attribute\{Delete, Get, Head, Options, Patch, Post, Put};

#[CoversClass(Get::class)]
#[CoversClass(Post::class)]
#[CoversClass(Put::class)]
#[CoversClass(Patch::class)]
#[CoversClass(Delete::class)]
#[CoversClass(Head::class)]
#[CoversClass(Options::class)]
final class HttpMethodAttributesTest extends TestCase
{

    /**
     * @return iterable<string, array{class-string<Get|Post|Put|Patch|Delete|Head|Options>, string}>
     */
    public static function attributeProvider(): iterable
    {
        yield 'GET' => [Get::class, 'GET'];
        yield 'POST' => [Post::class, 'POST'];
        yield 'PUT' => [Put::class, 'PUT'];
        yield 'PATCH' => [Patch::class, 'PATCH'];
        yield 'DELETE' => [Delete::class, 'DELETE'];
        yield 'HEAD' => [Head::class, 'HEAD'];
        yield 'OPTIONS' => [Options::class, 'OPTIONS'];
    }

    /**
     * @return iterable<string, array{class-string<Get|Post|Put|Patch|Delete|Head|Options>}>
     */
    public static function attributeClassProvider(): iterable
    {
        foreach (self::attributeProvider() as $httpMethod => [$className]) {
            yield $httpMethod => [$className];
        }
    }

    /**
     * @param class-string<Get|Post|Put|Patch|Delete|Head|Options> $className
     */
    #[DataProvider('attributeProvider')]
    public function testItDefinesItsHttpMethod(string $className, string $httpMethod): void
    {
        $this->assertSame([$httpMethod], (new $className('/path'))->methods);
    }

    /**
     * @param class-string<Get|Post|Put|Patch|Delete|Head|Options> $className
     */
    #[DataProvider('attributeClassProvider')]
    public function testPathNameAndPriorityHaveDefaultValues(string $className): void
    {
        $attribute = new $className();

        $this->assertSame('', $attribute->path);
        $this->assertSame('', $attribute->name);
        $this->assertSame(0, $attribute->priority);
    }

    /**
     * @param class-string<Get|Post|Put|Patch|Delete|Head|Options> $className
     */
    #[DataProvider('attributeClassProvider')]
    public function testItAcceptsNameAndPriority(string $className): void
    {
        $attribute = new $className('/users/{id}', name: 'users.show', priority: 42);

        $this->assertSame('/users/{id}', $attribute->path);
        $this->assertSame('users.show', $attribute->name);
        $this->assertSame(42, $attribute->priority);
    }

    /**
     * @param class-string<Get|Post|Put|Patch|Delete|Head|Options> $className
     */
    #[DataProvider('attributeClassProvider')]
    public function testItIsARepeatableMethodAttribute(string $className): void
    {
        $attributes = (new ReflectionClass($className))->getAttributes(Attribute::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE, $attributes[0]->newInstance()->flags);
    }
}
