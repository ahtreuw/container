<?php declare(strict_types=1);

namespace Container;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;

class ParametersTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = $this->createMock(FactoryInterface::class);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testWithBuiltInAndPassedParam(): void
    {
        $data = ['str' => 'str-val', 'number' => '14', 'decimal' => '15.67', 'boolean' => '0', 'mixed' => 'random value'];
        $expected = ['str' => 'str-val', 'number' => 14, 'decimal' => 15.67, 'boolean' => false, 'mixed' => 'random value'];

        $class = new ReflectionClass(new class ($this->factory) {
            public function __construct(FactoryInterface|ParametersInterface $obj)
            {
            }

            public function get(string|null $str, int $number, float $decimal, bool $boolean, $mixed)
            {
            }
        });

        $parameters = (new Parameters)->with('Anonymous::get', $class->getMethod('get'));
        self::assertFalse($parameters->isConstructor());

        $result = $parameters->make($this->container, ...$data);
        self::assertSame($expected, $result);

        $result = $parameters->make($this->container, ...array_values($data));
        self::assertSame($expected, $result);


        $parameters = (new Parameters)->with('Anonymous', $class->getMethod('__construct'));
        self::assertTrue($parameters->isConstructor());

        $result = $parameters->make($this->container, ...['obj' => $this->factory]);
        self::assertInstanceOf(FactoryInterface::class, $result['obj']);
    }

    public function testWithNoConstructor(): void
    {
        $parameters = (new Parameters)->with('Anonymous', null);
        self::assertTrue($parameters->isConstructor());
        $parameters = (new Parameters)->with('Anonymous::__construct', null);
        self::assertTrue($parameters->isConstructor());
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWithSelfBuild(): void
    {
        $class = new ReflectionClass(new class {
            public function get(FactoryInterface $factory = new Factory)
            {
            }
        });
        $parameters = (new Parameters)->with('Anonymous', $class->getMethod('get'));
        $result = $parameters->make($this->container);
        self::assertSame([], $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function testWithMultiBuiltIn(): void
    {
        $class = new ReflectionClass(new class {
            public function get(int|string $value = null)
            {
            }
        });

        $parameters = (new Parameters)->with('Anonymous', $class->getMethod('get'));

        $result = $parameters->make($this->container, 'hi');
        self::assertSame(['value' => 'hi'], $result);

        $result = $parameters->make($this->container, 13);
        self::assertSame(['value' => 13], $result);
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWithContainer(): void
    {
        $class = new ReflectionClass(new class {
            public function get(FactoryInterface $factory)
            {
            }
        });

        $parameters = (new Parameters)->with('Anonymous', $class->getMethod('get'));

        $this->container->expects($this->once())->method('has')->willReturn(true);
        $this->container->expects($this->once())->method('get')->willReturn($this->factory);

        $result = $parameters->make($this->container);
        self::assertSame(['factory' => $this->factory], $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function testWithObjects(): void
    {
        $class = new ReflectionClass(new class {
            public function method(FactoryInterface $factory)
            {
            }
        });

        $this->container->expects($this->any())->method('has')->willReturn(false);

        $parameters = (new Parameters)->with('Anonymous', $class->getMethod('method'));
        $result = $parameters->make($this->container);
        self::assertSame(['factory' => null], $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function testWithOptionalObjects(): void
    {
        $class = new ReflectionClass(new class {
            public function optional(FactoryInterface $factory = null)
            {
            }
        });
        $this->container->expects($this->any())->method('has')->willReturn(false);

        $parameters = (new Parameters)->with('Anonymous', $class->getMethod('optional'));
        $result = $parameters->make($this->container);
        self::assertSame([], $result);
    }
}