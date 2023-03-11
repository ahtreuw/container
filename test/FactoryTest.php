<?php declare(strict_types=1);

namespace Vulpes\Container;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
use Vulpes\Container\Parameter\ObjParam;
use Vulpes\Container\Parameter\ValParam;

class FactoryTest extends TestCase
{
    private FactoryInterface $factory;

    protected function setUp(): void
    {
        $this->factory = new Factory;
    }

    public function testCreateInstance(): void
    {
        $data = ['key' => 'value'];
        $instance = $this->factory->createInstance(ArrayObject::class, $data);
        self::assertSame($data, $instance->getArrayCopy());
    }

    public function testInvokeClosure(): void
    {
        $value = ['key' => 'value'];
        $result = $this->factory->invokeClosure(function ($value) {
            return $value;
        }, $value);
        self::assertSame($value, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testCreateParametersNotFoundException(): void
    {
        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionCode(NotFoundException::CREATE_PARAMETERS);
        $this->factory->createParameters('id', 'not existing class');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCreateParametersWithoutConstructor(): void
    {
        $result = $this->factory->createParameters('id', stdClass::class);
        self::assertInstanceOf(Parameters::class, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCreateParameterWithoutTypeOrBuiltIn()
    {
        $class = new class (null, null, null, $this->factory) {
            public function __construct(string|bool|null $a, ?string $b, $c, FactoryInterface $d, $e = 'value')
            {
            }
        };
        $parameters = $this->factory->createParameters('id', get_class($class));

        self::assertInstanceOf(Parameters::class, $parameters);
        self::assertCount(5, $parameters->getParameters());

        self::assertNull($parameters->getParameter(0)->getValue());
        self::assertNull($parameters->getParameter(1)->getValue());
        self::assertNull($parameters->getParameter(2)->getValue());

        self::assertEquals(FactoryInterface::class, $parameters->getParameter(3)->getValue());
        self::assertEquals('value', $parameters->getParameter(4)->getValue());

        self::assertInstanceOf(ValParam::class, $parameters->getParameter(0));
        self::assertInstanceOf(ValParam::class, $parameters->getParameter(1));
        self::assertInstanceOf(ValParam::class, $parameters->getParameter(2));
        self::assertInstanceOf(ObjParam::class, $parameters->getParameter(3)); // ObjParam
        self::assertInstanceOf(ValParam::class, $parameters->getParameter(4));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function testCreateParameterWithoutTypeFACTORY2Exception()
    {
        $class = new class ('a') {
            public function __construct(string|int $a)
            {
            }
        };

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionCode(ContainerException::BUILTIN);

        $this->factory->createParameters('id', get_class($class));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function testCreateParameterWithoutTypeFACTORY4Exception()
    {
        $class = new class ('a') {
            public function __construct(string $a)
            {
            }
        };

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionCode(ContainerException::BUILTIN);

        $this->factory->createParameters('id', get_class($class));
    }
    
//    /**
//     * @throws ContainerExceptionInterface
//     */
//    public function testReadConfigFile(): void
//    {
//        $this->parser->expects($this->once())->method('parseFile')->with('filename.yaml')->willReturn([
//            'conf' => ['Example' => null],
//            'args' => ['ARG_KEY' => null],
//        ]);
//
//        $this->storage->readConfigFile('filename.yaml');
//
//        self::assertTrue($this->storage->has('Example'));
//        self::assertTrue($this->storage->has('ARG_KEY'));
//    }
}
