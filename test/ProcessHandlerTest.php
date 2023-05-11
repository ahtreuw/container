<?php declare(strict_types=1);


namespace Container;

use JetBrains\PhpStorm\Pure;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ProcessHandlerTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = $this->createMock(FactoryInterface::class);
        $this->parameters = $this->createMock(ParametersInterface::class);
    }

    #[Pure] protected function createDTO(string $id, mixed $value, mixed ...$arguments): ProcessDTO
    {
        return new ProcessDTO($this->container, $id, $value, $arguments);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testCreateObject()
    {
        $handler = new ProcessHandler($this->factory);

        $dto = $this->createDTO('id', ProcessHandler::class, []);

        $this->container->expects($this->once())->method('set')->with('id', $handler);
        $this->parameters->expects($this->once())->method('make')->willReturn([]);

        $this->factory->expects($this->once())->method('createObject')->willReturn($handler);

        $result = $handler->createObject($dto, $this->parameters, ProcessHandler::class);

        self::assertInstanceOf(ProcessHandlerInterface::class, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Throwable
     */
    public function testCreateObjectException()
    {
        $handler = new ProcessHandler;

        $dto = $this->createDTO('id', ProcessHandler::class . 'PlusForNotExisting', []);

        $this->container->expects($this->never())->method('set');

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage('No entry was found ');

        $handler->createObject($dto, $this->parameters, ProcessHandler::class . 'PlusForNotExisting');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testCall()
    {
        $handler = new ProcessHandler;
        $dto = $this->createDTO('id', ProcessHandler::class . '::with', []);

        $this->container->expects($this->once())->method('get')->willReturn($handler);

        $result = $handler->call($dto, $this->parameters, ProcessHandler::class, 'with');
        self::assertInstanceOf(ProcessHandlerInterface::class, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testCallWithHandleArrayStr()
    {
        $handler = new ProcessHandler;
        $dto = $this->createDTO(ProcessHandler::class . '::with', [ProcessHandler::class, 'with'], []);
        $this->container->expects($this->once())->method('get')->willReturn($handler);
        self::assertInstanceOf(ProcessHandlerInterface::class, $handler->handle($dto));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testCallWithHandleArrayObj()
    {
        $handler = new ProcessHandler;
        $dto = $this->createDTO(ProcessHandler::class . '::with', [$handler, 'with'], []);
        $this->container->expects($this->never())->method('get');
        self::assertInstanceOf(ProcessHandlerInterface::class, $handler->handle($dto));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Throwable
     */
    public function testCallException()
    {
        $handler = new ProcessHandler;
        $dto = $this->createDTO('id', ProcessHandler::class . '::with', []);
        $this->container->expects($this->once())->method('get')->willReturn(null);

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage('No entry was found ');
        $handler->call($dto, $this->parameters, ProcessHandler::class, 'with');
    }

//    /**
//     * @throws ContainerExceptionInterface
//     * @throws NotFoundExceptionInterface
//     */
//    public function testProcessCall(): void
//    {
//        $processor = new ProcessHandler(factory: $this->factory);
//
//        $this->container->expects($this->once())->method('getParameters')
//            ->with(Container::class . '::has')
//            ->willReturn($this->parameters);
//
//        $this->parameters->expects($this->once())->method('make')
//            ->with($this->container, 'randomValue')
//            ->willReturn(['makeResultValue']);
//
//        $this->parameters->expects($this->once())->method('isConstructor')
//            ->willReturn(false);
//
//        $this->container->expects($this->once())->method('get')
//            ->with(Container::class)
//            ->willReturn($this->container);
//
//        $this->factory->expects($this->once())->method('call')
//            ->with([$this->container, 'has'], 'makeResultValue')
//            ->willReturn(true);
//
//        $result = $processor->process($this->container, 'id-string', Container::class . '::has', 'randomValue');
//
//        self::assertTrue($result);
//    }
//
//    /**
//     * @throws ContainerExceptionInterface
//     */
//    public function testProcessNotFoundExceptionInterface(): void
//    {
//        $processor = new ProcessHandler(factory: $this->factory);
//
//        $this->container->expects($this->once())->method('getParameters')
//            ->with(Container::class . '::notExistingMethod')
//            ->willReturn($this->parameters);
//
//        $this->parameters->expects($this->once())->method('make')
//            ->with($this->container)
//            ->willReturn([]);
//
//        $this->parameters->expects($this->once())->method('isConstructor')
//            ->willReturn(false);
//
//        $this->container->expects($this->once())->method('get')
//            ->with(Container::class)
//            ->willReturn($this->container);
//
//        $this->factory->expects($this->never())->method('call');
//
//        self::expectException(NotFoundExceptionInterface::class);
//        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', Container::class . '::notExistingMethod'));
//
//        $processor->process($this->container, 'id-string', Container::class . '::notExistingMethod');
//    }
}