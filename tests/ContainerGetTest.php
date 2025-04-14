<?php declare(strict_types=1);

namespace Tests;

use Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
use Tests\TestObjects\TestClassWithDefaultParameterValues;
use Tests\TestObjects\TestClassWithOptionalParameter;

class ContainerGetTest extends TestCase
{
    public function testImplementation(): void
    {
        self::assertInstanceOf(ContainerInterface::class, new Container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetOptional(): void
    {
        $container = new Container;

        /**
         * @var TestClassWithOptionalParameter $object
         */
        $object = $container->get(TestClassWithOptionalParameter::class);

        self::assertInstanceOf(TestClassWithOptionalParameter::class, $object);

        self::assertNull($object->arrayAccess);
        self::assertNull($object->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetContainer(): void
    {
        $container = new Container;
        self::assertSame($container, $container->get(Container::class));
        self::assertSame($container, $container->get(ContainerInterface::class));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetWithDefaultParameterValues(): void
    {
        $container = new Container;
        /**
         * @var TestClassWithDefaultParameterValues $obj
         */
        $obj = $container->get(TestClassWithDefaultParameterValues::class);
        self::assertNull($obj->name);
        self::assertInstanceOf(stdClass::class, $obj->object);

        self::assertInstanceOf(stdClass::class, $container->get(stdClass::class));
    }

}
