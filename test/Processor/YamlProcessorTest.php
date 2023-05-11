<?php declare(strict_types=1);

namespace Container\Processor;

use ArrayObject;
use Container\ContainerInterface;
use Container\FactoryInterface;
use Container\ProcessHandlerInterface;
use Container\ProcessDTO;
use Container\ProcessorInterface;
use JetBrains\PhpStorm\Pure;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
use Throwable;

class YamlProcessorTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = $this->createMock(FactoryInterface::class);
        $this->handler = $this->createMock(ProcessHandlerInterface::class);
    }

    #[Pure] protected function createDTO(string $id, mixed $value, mixed ...$arguments): ProcessDTO
    {
        return new ProcessDTO($this->container, $id, $value, $arguments);
    }

    /**
     * @dataProvider handlingProvider
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testHandle(string $id, mixed $value, int $exactly): void
    {
        $processor = new YamlProcessor;

        $this->handler->expects($this->exactly($exactly))->method('handle');

        $processor->process($this->createDTO($id, $value), $this->handler);
    }

    public static function handlingProvider(): array
    {
        return [
            ['id', ContainerInterface::class, 1],
            ['storage-id', ['class' => ProcessorInterface::class, 'params' => []], 1],
            ['storage-id', ['class' => YamlProcessor::class, 'params' => []], 0],
            [ProcessorInterface::class, ['params' => []], 1],
            [YamlProcessor::class, ['params' => []], 0],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testProcessCreate(): void
    {
        $processor = new YamlProcessor($this->factory);

        $this->factory->expects($this->once())->method('createObject')
            ->with(YamlProcessor::class)->willReturn($processor);

        $this->container->expects($this->once())->method('set')
            ->with(YamlProcessor::class, $processor);

        $dto = $this->createDTO(YamlProcessor::class, ['class' => YamlProcessor::class, 'params' => []]);

        $result = $processor->process($dto, $this->handler);

        self::assertSame($processor, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testParams(): void
    {
        $processor = new YamlProcessor($this->factory);

        $this->factory->expects($this->once())->method('call')
            ->with('getenv', 'env-key')->willReturn('env-val');

        $this->factory->expects($this->once())->method('createObject')
            ->with(stdClass::class, 'env-val', 'val-value', 'argument-val')->willReturn(new stdClass);

        $dto = $this->createDTO(
            stdClass::class,
            ['params' => [['env' => 'env-key'], ['val' => 'val-value'], ['arg' => 'argument-key']]],
            'argument-val'
        );
        $processor->process($dto, $this->handler);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testParamsWithArgs(): void
    {
        $processor = new YamlProcessor;

        $dto = $this->createDTO(ArrayObject::class,
            ['params' => [['env:array' => 'env-key']]], ...['array' => ['env-key' => 'env-val']]);

        $result = $processor->process($dto, $this->handler);

        self::assertEquals('env-val', $result['env-key']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testObjParamsWithContainer(): void
    {
        $this->factory->expects($this->once())->method('createObject')
            ->willReturn(new stdClass)->with(stdClass::class, 'my-class');

        $this->container->expects($this->any())->method('has')
            ->with('my-className')->willReturn(true);

        $this->container->expects($this->once())->method('get')
            ->with('my-className')->willReturn('my-class');

        $processor = new YamlProcessor($this->factory);
        $dto = $this->createDTO(stdClass::class, ['params' => [['obj' => 'my-className']]]);

        $processor->process($dto, $this->handler);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testArgParamsWithContainer(): void
    {
        $this->factory->expects($this->once())->method('createObject')
            ->willReturn(new stdClass)->with(stdClass::class, 'my-arg');

        $this->container->expects($this->once())->method('has')
            ->with('my-argName')->willReturn(true);

        $this->container->expects($this->once())->method('get')
            ->with('my-argName')->willReturn('my-arg');

        $processor = new YamlProcessor($this->factory);
        $dto = $this->createDTO(stdClass::class, ['params' => [['arg' => 'my-argName']]]);
        $processor->process($dto, $this->handler);
    }

}