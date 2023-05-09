<?php declare(strict_types=1);


namespace Container;

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
        $parametersWith = $this->createMock(ParametersInterface::class);
        $parametersAnother = $this->createMock(ParametersInterface::class);

        $parametersConstruct = $this->createMock(ParametersInterface::class);
        $parametersConstruct->expects($this->once())->method('with')->willReturn($parametersWith);

        $container = new Container(parameters: $parametersConstruct);

        self::assertSame($parametersWith, $container->getParameters('id-string'));
        self::assertSame($parametersWith, $container->getParameters('id-string'));


        $container = new Container(
            storage: ['id-string' => $parametersAnother],
            parameters: $parametersConstruct
        );

        self::assertSame($parametersAnother, $container->getParameters('id-string'));
        self::assertSame($parametersAnother, $container->getParameters('id-string'));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testGet(): void
    {
        $container = new Container(storage: ['id-string' => 'justAValue']);
        self::assertSame('justAValue', $container->get('id-string'));


        $processor = $container->get(ProcessorInterface::class);
        self::assertInstanceOf(Processor::class, $processor);
        self::assertInstanceOf(ProcessorInterface::class, $processor);


        $container = new Container(storage: ['id-string' => Processor::class]);
        $processor = $container->get('id-string');
        self::assertInstanceOf(Processor::class, $processor);


        $container = new Container(storage: ['id-string' => $processor]);
        self::assertSame($processor, $container->get('id-string'));


        $container = new Container(storage: ['id-string' => function () use ($processor) {
            return $processor;
        }]);
        self::assertSame($processor, $container->get('id-string'));
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

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf('Error while retrieving the entry %s.', 'exceptionThrowerEntity'));

        $container->get('exceptionThrowerEntity');
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testCall(): void
    {
        $container = new Container(storage: ['id-string' => 'id-string-value']);

        self::assertSame('id-string-value', $container->call($container, 'get', 'id-string'));
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
