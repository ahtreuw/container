<?php declare(strict_types=1);

namespace Tests\Container;

use Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerNotFoundExceptionsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        eval('
        namespace Tests\\Container\\ContainerNotFoundExceptionsTest\\TestObjects; 
        interface SomeServiceInterface {}
        class TestClassWithSomeServiceInterfaceParameter { 
            public function __construct(SomeServiceInterface $service) {} 
        }
        class TestClassWithIntParam { 
            public function __construct(int $value) {} 
        }');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testGetClass(): void
    {
        $container = new Container;

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', 'notExistingEntity'));

        $container->get('notExistingEntity');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testGetInterface(): void
    {
        $container = new Container;

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', 'notExistingEntityInterface'));

        $container->get('notExistingEntityInterface');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testWithBuiltInNotOptionalNotAllowsNullParameter(): void
    {
        $container = new Container;

        $className = '\Tests\Container\ContainerNotFoundExceptionsTest\TestObjects\TestClassWithIntParam';

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage("No entry was found for $className::\$value identifier.");

        $container->get($className);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testWithNotImplementableParameter(): void
    {
        $container = new Container;

        $className = '\Tests\Container\ContainerNotFoundExceptionsTest\TestObjects\TestClassWithSomeServiceInterfaceParameter';

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage("No entry was found for $className::\$service identifier.");

        $container->get($className);
    }
}
