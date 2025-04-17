<?php declare(strict_types=1);

namespace Tests\Container;

use ArrayAccess;
use ArrayObject;
use Container\Container;
use Container\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerGetStorageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        eval('
        namespace Tests\\Container\\ContainerGetStorageTest\\TestObjects; 
        
        class TestClassWithOptionalParameter
        {
            public null|\Psr\Container\ContainerInterface $container;
        
            public function __construct(
                public null|\ArrayAccess|(\Stringable&\Throwable) $arrayAccess,
                public null|\ArrayAccess                          $onlyArrayAccess,
                \Psr\Container\ContainerInterface                 $container = null
            )
            {
                $this->container = $container;
            }
        }');
    }

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
        $id = 'Tests\Container\ContainerGetStorageTest\TestObjects\TestClassWithOptionalParameter';

        $container = new Container;

        $container->set($id . 'Interface::params', $params = [
            'container' => $container
        ]);

        $object = $container->get($id . 'Interface');

        self::assertInstanceOf($id, $object);

        self::assertSame($params, $container->get($id . 'Interface::params'));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParametersWithAliasParam(): void
    {
        $id = 'Tests\Container\ContainerGetStorageTest\TestObjects\TestClassWithOptionalParameter';

        $container = new Container;

        $container->set($id . 'Interface::container', $container);

        $object = $container->get($id . 'Interface');

        self::assertInstanceOf($id, $object);

        self::assertSame($container, $container->get($id . 'Interface::container'));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParametersWithIdParam(): void
    {
        $id = 'Tests\Container\ContainerGetStorageTest\TestObjects\TestClassWithOptionalParameter';
        $container = new Container;

        $container->set($id . '::container', $container);

        $object = $container->get($id);

        self::assertInstanceOf($id, $object);

        self::assertSame($container, $container->get($id . '::container'));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParametersWithAliasAndObjectNameParam(): void
    {
        $id = 'Tests\Container\ContainerGetStorageTest\TestObjects\TestClassWithOptionalParameter';
        $container = new Container;

        $container->set($id . 'Interface::' . ContainerInterface::class, $container);

        $object = $container->get($id . 'Interface');

        self::assertInstanceOf($id, $object);

        self::assertSame($container, $container->get($id . 'Interface::' . ContainerInterface::class));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParametersWithIdAndObjectNameParam(): void
    {
        $id = 'Tests\Container\ContainerGetStorageTest\TestObjects\TestClassWithOptionalParameter';
        $container = new Container;

        $container->set($id . '::' . ContainerInterface::class, $container);

        $object = $container->get($id);

        self::assertInstanceOf($id, $object);

        self::assertSame($container, $container->get($id . '::' . ContainerInterface::class));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetFromStorageParametersWithParameterAndObjectName(): void
    {
        $id = 'Tests\Container\ContainerGetStorageTest\TestObjects\TestClassWithOptionalParameter';
        $container = new Container;

        $container->set(ContainerInterface::class . '::container', $container);

        $object = $container->get($id);

        self::assertInstanceOf($id, $object);

        self::assertSame($container, $container->get(ContainerInterface::class . '::container'));
        self::assertSame($container, $object->container);
        self::assertNull($object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetParameterStorageValue(): void
    {
        $id = 'Tests\Container\ContainerGetStorageTest\TestObjects\TestClassWithOptionalParameter';
        $container = new Container;

        $container->set(ArrayAccess::class, $arrayObject = new ArrayObject([]));
        $container->set(substr(ContainerInterface::class, 0, -9), $container);

        $object = $container->get($id);

        self::assertSame($container, $object->container);
        self::assertSame($arrayObject, $object->arrayAccess);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetParameterAllowsNull(): void
    {
        $id = 'Tests\Container\ContainerGetStorageTest\TestObjects\TestClassWithOptionalParameter';
        $container = new Container;

        $object = $container->get($id);

        self::assertNull($object->container);
        self::assertNull($object->arrayAccess);
    }

}
