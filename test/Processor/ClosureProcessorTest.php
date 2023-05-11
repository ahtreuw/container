<?php declare(strict_types=1);

namespace Container\Processor;

use Container\ContainerInterface;
use Container\ProcessHandlerInterface;
use Container\ProcessDTO;
use JetBrains\PhpStorm\Pure;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ClosureProcessorTest extends TestCase
{

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
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
        $processor = new ClosureProcessor;

        $this->handler->expects($this->exactly($exactly))->method('handle');

        $processor->process($this->createDTO($id, $value), $this->handler);
    }

    public static function handlingProvider(): array
    {
        return [
            ['id', ContainerInterface::class, 1],
            ['id', ClosureProcessor::class, 1],
            ['id', ['class' => ClosureProcessor::class, 'params' => []], 1],
            [ClosureProcessor::class, ['params' => []], 1],
            ['id', function () {
            }, 0],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function testProcess(): void
    {
        $processor = new ClosureProcessor;

        $this->handler->expects($this->never())->method('handle');

        $id = 'hello';
        $args = [$id, 'hello-args', 'bye'];

        $this->container->expects($this->once())->method('set')
            ->with($id, [$this->container, $id, $args]);

        $result = $processor->process($this->createDTO($id, function (ContainerInterface $container, string $id, mixed ...$args) {
            return [$container, $id, $args];
        }, ...$args), $this->handler);

        self::assertSame([$this->container, $id, $args], $result);
    }

}