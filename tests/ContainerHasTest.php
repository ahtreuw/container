<?php declare(strict_types=1);

namespace Tests\Container;

use Container\Container;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Container\TestObjects\TestClassInterface;

class ContainerHasTest extends TestCase
{

    public static function hasCases(): Generator
    {
        yield ['key'];
        yield ['keyInterface'];
        yield [Container::class];
        yield [ContainerInterface::class];
        yield [TestClassInterface::class];
    }

    #[DataProvider('hasCases')]
    public function testHas(string $id): void
    {
        self::assertTrue((new Container(['key' => 'value']))->has($id));
    }

    public function testHasNotValue(): void
    {
        self::assertFalse((new Container(['key' => 'value']))->has('value'));
    }

}
