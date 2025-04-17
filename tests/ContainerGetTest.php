<?php declare(strict_types=1);

namespace Tests\Container;

use Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

class ContainerGetTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        eval('
        namespace Tests\\ContainerGetTest\\TestObjects; 
        
        class TestClassWithDefaultParameterValues
        {
            public function __construct(public $name, public $object = new \stdClass) {}
        }
        class TestClassWithOptionalParameter
        {
            public null|\Psr\Container\ContainerInterface $container;
        
            public function __construct(
                public null|\ArrayAccess|(\Stringable&\Throwable) $arrayAccess,
                \Psr\Container\ContainerInterface                 $container = null
            )
            {
                $this->container = $container;
            }
        }');
    }
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
        $id = 'Tests\ContainerGetTest\TestObjects\TestClassWithOptionalParameter';
        $container = new Container;

        $object = $container->get($id);

        self::assertInstanceOf($id, $object);

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
        $id = 'Tests\ContainerGetTest\TestObjects\TestClassWithDefaultParameterValues';
        $container = new Container;

        $obj = $container->get($id);

        self::assertNull($obj->name);
        self::assertInstanceOf(stdClass::class, $obj->object);

        self::assertInstanceOf(stdClass::class, $container->get(stdClass::class));
    }

}
