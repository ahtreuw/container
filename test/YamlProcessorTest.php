<?php declare(strict_types=1);

namespace Container;

use ArrayObject;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

class YamlProcessorTest extends TestCase
{

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->reflector = $this->createMock(ReflectorInterface::class);
    }

    public function testHandle(): void
    {
        $processor = new YamlProcessor;

        self::assertFalse($processor->handle($this->container, 'id', ContainerInterface::class));

        self::assertFalse($processor->handle($this->container, 'storage-id', ['class' => ProcessorInterface::class, 'params' => []]));
        self::assertTrue($processor->handle($this->container, 'storage-id', ['class' => YamlProcessor::class, 'params' => []]));

        self::assertFalse($processor->handle($this->container, ProcessorInterface::class, ['params' => []]));
        self::assertTrue($processor->handle($this->container, YamlProcessor::class, ['params' => []]));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testProcessCreate(): void
    {
        $processor = new YamlProcessor($this->reflector);

        $this->reflector->expects($this->once())->method('create')->with(YamlProcessor::class)->willReturn($processor);
        $this->container->expects($this->once())->method('set')->with(YamlProcessor::class, $processor);

        $result = $processor->process($this->container, YamlProcessor::class, ['class' => YamlProcessor::class, 'params' => []]);

        self::assertSame($processor, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testParams(): void
    {
        $processor = new YamlProcessor($this->reflector);

        $this->reflector->expects($this->once())->method('call')->with('getenv', 'env-key')->willReturn('env-val');
        $this->reflector->expects($this->once())->method('create')->with(stdClass::class, 'env-val', 'val-value', 'argument-val')->willReturn(new stdClass);

        $processor->process($this->container, stdClass::class, ['params' => [['env' => 'env-key'], ['val' => 'val-value'], ['arg' => 'argument-key']]], 'argument-val');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testParamsWithArgs(): void
    {
        $processor = new YamlProcessor;

        $result = $processor->process($this->container, ArrayObject::class,
            ['params' => [['env:array' => 'env-key']]], ...['array' => ['env-key' => 'env-val']]);

        self::assertEquals('env-val', $result['env-key']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testObjParamsWithContainer(): void
    {
        $this->reflector->expects($this->once())->method('create')->willReturn(new stdClass)->with(stdClass::class, 'my-class');

        $this->container->expects($this->any())->method('has')->with('my-className')->willReturn(true);
        $this->container->expects($this->once())->method('get')->with('my-className')->willReturn('my-class');

        $processor = new YamlProcessor($this->reflector);
        $processor->process($this->container, stdClass::class, ['params' => [['obj' => 'my-className']]]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testArgParamsWithContainer(): void
    {
        $this->reflector->expects($this->once())->method('create')->willReturn(new stdClass)->with(stdClass::class, 'my-arg');

        $this->container->expects($this->once())->method('has')->with('my-argName')->willReturn(true);
        $this->container->expects($this->once())->method('get')->with('my-argName')->willReturn('my-arg');

        $processor = new YamlProcessor($this->reflector);
        $processor->process($this->container, stdClass::class, ['params' => [['arg' => 'my-argName']]]);
    }

}