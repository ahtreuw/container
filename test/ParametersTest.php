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
        $this->reflector = $this->createMock(ReflectorInterface::class);
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

        $class = new ReflectionClass(new class ($this->reflector) {
            public function __construct(ReflectorInterface|ParametersInterface $obj)
            {
            }

            public function get(string|null $str, int $number, float $decimal, bool $boolean, $mixed)
            {
            }
        });

        $parameters = (new Parameters)->with('Anonym::get', $class->getMethod('get'));
        self::assertFalse($parameters->isConstructor());

        $result = $parameters->make($this->container, ...$data);
        self::assertSame($expected, $result);

        $result = $parameters->make($this->container, ...array_values($data));
        self::assertSame($expected, $result);


        $parameters = (new Parameters)->with('Anonym', $class->getMethod('__construct'));
        self::assertTrue($parameters->isConstructor());

        $result = $parameters->make($this->container, ...['obj' => $this->reflector]);
        self::assertInstanceOf(ReflectorInterface::class, $result['obj']);
    }

    public function testWithNoConstructor(): void
    {
        $parameters = (new Parameters)->with('Anonym', null);
        self::assertTrue($parameters->isConstructor());
        $parameters = (new Parameters)->with('Anonym::__construct', null);
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
            public function get(ReflectorInterface $reflector = new Reflector)
            {
            }
        });
        $parameters = (new Parameters)->with('Anonym', $class->getMethod('get'));
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

        $parameters = (new Parameters)->with('Anonym', $class->getMethod('get'));

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
            public function get(ReflectorInterface $reflector)
            {
            }
        });

        $parameters = (new Parameters)->with('Anonym', $class->getMethod('get'));

        $this->container->expects($this->once())->method('has')->willReturn(true);
        $this->container->expects($this->once())->method('get')->willReturn($this->reflector);

        $result = $parameters->make($this->container);
        self::assertSame(['reflector' => $this->reflector], $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function testWithObjects(): void
    {
        $class = new ReflectionClass(new class {
            public function method(ReflectorInterface $reflector)
            {
            }
        });

        $this->container->expects($this->any())->method('has')->willReturn(false);

        $parameters = (new Parameters)->with('Anonym', $class->getMethod('method'));
        $result = $parameters->make($this->container);
        self::assertSame(['reflector' => null], $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function testWithOptionalObjects(): void
    {
        $class = new ReflectionClass(new class {
            public function optional(ReflectorInterface $reflector = null)
            {
            }
        });
        $this->container->expects($this->any())->method('has')->willReturn(false);

        $parameters = (new Parameters)->with('Anonym', $class->getMethod('optional'));
        $result = $parameters->make($this->container);
        self::assertSame([], $result);
    }
}