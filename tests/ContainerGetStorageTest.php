<?php declare(strict_types=1);

namespace Tests;

use ArrayAccess;
use ArrayObject;
use Container\Container;
use Container\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestObjects\TestClassWithOptionalParameter;

class ContainerGetStorageTest extends TestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromClosure(): void
    {
        $containerException = new NotFoundException('another-key', 13);
        $container = new Container;

        $container->set('something', 'keyInterface');
        $container->set('key', function ($containerParameter, $id, $alias) use ($container, $containerException) {
            self::assertSame($container, $containerParameter);
            self::assertSame('key', $id);
            self::assertSame('keyInterface', $alias);
            return $containerException;
        });

        self::assertSame($containerException, $container->get('something'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParameters(): void
    {
        $container = new Container;

        $container->set(TestClassWithOptionalParameter::class . 'Interface::params', $params = [
            'container' => $container
        ]);

        /**
         * @var TestClassWithOptionalParameter $object
         */
        $object = $container->get(TestClassWithOptionalParameter::class . 'Interface');

        self::assertInstanceOf(TestClassWithOptionalParameter::class, $object);

        self::assertSame($params, $container->get(TestClassWithOptionalParameter::class . 'Interface::params'));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParametersWithAliasParam(): void
    {
        $container = new Container;

        $container->set(TestClassWithOptionalParameter::class . 'Interface::container', $container);

        /**
         * @var TestClassWithOptionalParameter $object
         */
        $object = $container->get(TestClassWithOptionalParameter::class . 'Interface');

        self::assertInstanceOf(TestClassWithOptionalParameter::class, $object);

        self::assertSame($container, $container->get(TestClassWithOptionalParameter::class . 'Interface::container'));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParametersWithIdParam(): void
    {
        $container = new Container;

        $container->set(TestClassWithOptionalParameter::class . '::container', $container);

        /**
         * @var TestClassWithOptionalParameter $object
         */
        $object = $container->get(TestClassWithOptionalParameter::class);

        self::assertInstanceOf(TestClassWithOptionalParameter::class, $object);

        self::assertSame($container, $container->get(TestClassWithOptionalParameter::class . '::container'));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParametersWithAliasAndObjectNameParam(): void
    {
        $container = new Container;

        $container->set(TestClassWithOptionalParameter::class . 'Interface::' . ContainerInterface::class, $container);

        /**
         * @var TestClassWithOptionalParameter $object
         */
        $object = $container->get(TestClassWithOptionalParameter::class . 'Interface');

        self::assertInstanceOf(TestClassWithOptionalParameter::class, $object);

        self::assertSame($container, $container->get(TestClassWithOptionalParameter::class . 'Interface::' . ContainerInterface::class));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParametersWithIdAndObjectNameParam(): void
    {
        $container = new Container;

        $container->set(TestClassWithOptionalParameter::class . '::' . ContainerInterface::class, $container);

        /**
         * @var TestClassWithOptionalParameter $object
         */
        $object = $container->get(TestClassWithOptionalParameter::class);

        self::assertInstanceOf(TestClassWithOptionalParameter::class, $object);

        self::assertSame($container, $container->get(TestClassWithOptionalParameter::class . '::' . ContainerInterface::class));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetParameterStorageValue(): void
    {
        $container = new Container;

        $container->set(ArrayAccess::class, $arrayObject = new ArrayObject([]));
        $container->set(substr(ContainerInterface::class, 0, -9), $container);

        /**
         * @var TestClassWithOptionalParameter $object
         */
        $object = $container->get(TestClassWithOptionalParameter::class);

        self::assertSame($container, $object->container);
        self::assertSame($arrayObject, $object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetParameterAllowsNull(): void
    {
        $container = new Container;

        /**
         * @var TestClassWithOptionalParameter $object
         */
        $object = $container->get(TestClassWithOptionalParameter::class);

        self::assertNull($object->container);
        self::assertNull($object->arrayAccess);
    }

}
