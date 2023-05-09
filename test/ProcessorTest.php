<?php declare(strict_types=1);


namespace Container;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

class ProcessorTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->reflector = $this->createMock(ReflectorInterface::class);
        $this->parameters = $this->createMock(ParametersInterface::class);
    }

    public function testHandle(): void
    {
        $processor = new Processor;

        self::assertTrue($processor->handle($this->container, 'id', function () {
        }));
        self::assertTrue($processor->handle($this->container, 'id', $this->parameters));
        self::assertTrue($processor->handle($this->container, 'id', new stdClass));
        self::assertTrue($processor->handle($this->container, 'id', stdClass::class));

        self::assertFalse($processor->handle($this->container, 'id', 'notAClassName'));
        self::assertFalse($processor->handle($this->container, 'id', ContainerInterface::class));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testProcessCreate(): void
    {
        $processor = new Processor;

        $this->container->expects($this->once())->method('set')->with('id-string', 'ab');

        self::assertSame('ab', $processor->process($this->container, 'id-string', function (ContainerInterface $container, $id, $a, $b) {
            self::assertSame($this->container, $container);
            self::assertSame('id-string', $id);
            return $a . $b;
        }, 'a', 'b'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testProcessCall(): void
    {
        $processor = new Processor(reflector: $this->reflector);

        $this->container->expects($this->once())->method('getParameters')
            ->with(Container::class . '::has')
            ->willReturn($this->parameters);

        $this->parameters->expects($this->once())->method('make')
            ->with($this->container, 'randomValue')
            ->willReturn(['makeResultValue']);

        $this->parameters->expects($this->once())->method('isConstructor')
            ->willReturn(false);

        $this->container->expects($this->once())->method('get')
            ->with(Container::class)
            ->willReturn($this->container);

        $this->reflector->expects($this->once())->method('call')
            ->with([$this->container, 'has'], 'makeResultValue')
            ->willReturn(true);

        $result = $processor->process($this->container, 'id-string', Container::class . '::has', 'randomValue');

        self::assertTrue($result);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testProcessNotFoundExceptionInterface(): void
    {
        $processor = new Processor(reflector: $this->reflector);

        $this->container->expects($this->once())->method('getParameters')
            ->with(Container::class . '::notExistingMethod')
            ->willReturn($this->parameters);

        $this->parameters->expects($this->once())->method('make')
            ->with($this->container)
            ->willReturn([]);

        $this->parameters->expects($this->once())->method('isConstructor')
            ->willReturn(false);

        $this->container->expects($this->once())->method('get')
            ->with(Container::class)
            ->willReturn($this->container);

        $this->reflector->expects($this->never())->method('call');

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', Container::class . '::notExistingMethod'));

        $processor->process($this->container, 'id-string', Container::class . '::notExistingMethod');
    }
}