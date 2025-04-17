<?php declare(strict_types=1);

namespace Tests\Container;

use ArrayAccess;
use Container\Container;
use Container\NotFoundException;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Throwable;

class ContainerHasTest extends TestCase
{

    public static function hasCases(): Generator
    {
        yield ['key'];
        yield ['keyInterface'];
        yield [Container::class];
        yield [ContainerInterface::class];
        yield [NotFoundException::class];
    }

    #[DataProvider('hasCases')]
    public function testHas(string $id): void
    {
        self::assertTrue((new Container(['key' => 'value']))->has($id));
    }

    public static function hasNotCases(): Generator
    {
        yield ['value'];
        yield [Throwable::class];
        yield [ArrayAccess::class];
    }

    #[DataProvider('hasNotCases')]
    public function testHasNotValue(string $id): void
    {
        self::assertFalse((new Container(['key' => 'value']))->has($id));
    }

}
