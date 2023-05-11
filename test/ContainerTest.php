<?php declare(strict_types=1);


namespace Container;

use Closure;
use Container\Processor\ClosureProcessor;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ContainerTest extends TestCase
{
    public function testImplementation(): void
    {
        $container = new Container;
        self::assertInstanceOf(ContainerInterface::class, $container);
        self::assertInstanceOf(\Psr\Container\ContainerInterface::class, $container);
    }

    public function testHas(): void
    {
        $container = new Container(['key' => 'value']);

        self::assertTrue($container->has('key'));
        self::assertTrue($container->has('key'));
        self::assertTrue($container->has(Container::class));
        self::assertTrue($container->has(Container::class . '::get'));
        self::assertTrue($container->has(Container::class . '::has'));
        self::assertTrue($container->has(ContainerInterface::class));

        self::assertFalse($container->has(Container::class . '::notExistingMethodName'));

        self::assertFalse($container->has(\Psr\Container\ContainerInterface::class));
        $container->set(\Psr\Container\ContainerInterface::class, ContainerInterface::class);
        self::assertTrue($container->has(\Psr\Container\ContainerInterface::class));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetParameters(): void
    {
        $fromWith = $this->createMock(ParametersInterface::class);
        $fromStorage = $this->createMock(ParametersInterface::class);
        $fromConstructor = $this->createMock(ParametersInterface::class);

        $fromConstructor->expects($this->once())->method('with')->willReturn($fromWith);

        $container = new Container(storage: ['from-storage' => $fromStorage], parameters: $fromConstructor);

        self::assertSame($fromWith, $container->getParameters('from-with'));
        self::assertSame($fromStorage, $container->getParameters('from-storage'));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testGetAndSet(): void
    {
        $container = new Container(storage: ['id-string' => ['justANotScalarValue']]);
        self::assertSame(['justANotScalarValue'], $container->get('id-string'));

        $processHandler = $container->get(ProcessHandlerInterface::class);
        self::assertInstanceOf(ProcessHandlerInterface::class, $processHandler);


        $container->set('id-string', ClosureProcessor::class);
        $closureProcessor = $container->get('id-string');
        self::assertInstanceOf(ClosureProcessor::class, $closureProcessor);
        self::assertInstanceOf(ProcessorInterface::class, $closureProcessor);


        $container->set('id-string', $processHandler);
        self::assertSame($processHandler, $container->get('id-string'));


        $container->set('id-string', function () use ($processHandler) {
            return $processHandler;
        });
        self::assertInstanceOf(Closure::class, $container->get('id-string'));

        $container->add(new ClosureProcessor);
        self::assertSame($processHandler, $container->get('id-string'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testGetNotFoundExceptionInterface(): void
    {
        $container = new Container;

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', 'notExistingEntity'));

        $container->get('notExistingEntity');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function testGetContainerExceptionInterface(): void
    {
        $container = new Container(storage: ['exceptionThrowerEntity' => function () {
            throw new Exception;
        }]);
        $container->add(new ClosureProcessor);

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf('Error while retrieving the entry %s.', 'exceptionThrowerEntity'));

        $container->get('exceptionThrowerEntity');
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws Throwable
     * @throws NotFoundExceptionInterface
     */
    public function testCallables(): void
    {
        $container = new Container(storage: ['id-string' => ['not-scalar-value']]);
        self::assertSame(['not-scalar-value'], $container->call($container, 'get', 'id-string'));
        self::assertEquals([], $container->get(StorageCollector::class . '::getStorage'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Throwable
     */
    public function testCallNotFoundExceptionInterface(): void
    {
        $container = new Container;

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', Container::class . '::notExistingMethod'));

        $container->call($container, 'notExistingMethod');
    }
}
