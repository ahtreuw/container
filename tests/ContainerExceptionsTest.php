<?php declare(strict_types=1);

namespace Tests\Container;

use Container\Container;
use Container\ContainerException;
use Container\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Tests\Container\TestObjects\TestAliasClassInterface;
use Tests\Container\TestObjects\TestClassWithExceptionOnConstruct;
use Tests\Container\TestObjects\TestClassWithRecursiveParameter;

class ContainerExceptionsTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function testGetWithSourceRecursion(): void
    {
        $container = new Container;

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf(ContainerException::CONTAINER_ERROR, TestAliasClassInterface::class));
        self::expectExceptionMessage(ContainerException::CIRCULAR_DEPENDENCY_MESSAGE);

        $container->get(TestAliasClassInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testGetWithRecursiveParameter(): void
    {
        $container = new Container;

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf(ContainerException::CONTAINER_ERROR, TestClassWithRecursiveParameter::class));
        self::expectExceptionMessage(ContainerException::PARAMETER_DEPENDENCY_MESSAGE);

        $container->get(TestClassWithRecursiveParameter::class);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testContainerClosureException(): void
    {
        $runtimeException = new RuntimeException('My runtime error message', 13);
        $container = new Container(['key' => function () use ($runtimeException) {
            throw $runtimeException;
        }]);

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf(ContainerException::CONTAINER_ERROR, 'key'));
        self::expectExceptionMessage(ContainerException::CLOSURE_EXCEPTION_MESSAGE);

        try {
            $container->get('key');
        } catch (ContainerExceptionInterface $e) {
            self::assertSame($runtimeException, $e->getPrevious());
            throw $e;
        }
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testContainerClosureExceptionWithContainerException(): void
    {
        $containerException = new ContainerException($id = 'another-key', 13);
        $container = new Container(['key' => function () use ($containerException) {
            throw $containerException;
        }]);

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf(ContainerException::CONTAINER_ERROR, $id));

        try {
            $container->get('key');
        } catch (ContainerExceptionInterface $e) {
            self::assertNull($e->getPrevious());
            throw $e;
        }
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testContainerClosureExceptionWithNotFoundException(): void
    {
        $containerException = new NotFoundException($id = 'another-key', 13);
        $container = new Container(['key' => function () use ($containerException) {
            throw $containerException;
        }]);

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', $id));

        try {
            $container->get('key');
        } catch (ContainerExceptionInterface $e) {
            self::assertNull($e->getPrevious());
            throw $e;
        }
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testContainerConstructException(): void
    {
        $runtimeException = new RuntimeException('My runtime error message', 13);
        $container = new Container([
            TestClassWithExceptionOnConstruct::class . '::params' => [
                'exception' => $runtimeException
            ],
            'key' => TestClassWithExceptionOnConstruct::class,
        ]);

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf(ContainerException::CONTAINER_ERROR, 'key'));
        self::expectExceptionMessage(ContainerException::CREATE_EXCEPTION_MESSAGE);

        try {
            $container->get('key');
        } catch (ContainerExceptionInterface $e) {
            self::assertSame($runtimeException, $e->getPrevious());
            throw $e;
        }
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testContainerConstructExceptionWithContainerException(): void
    {
        $containerException = new ContainerException($id = 'another-key', 13);
        $container = new Container([
            TestClassWithExceptionOnConstruct::class . '::params' => [
                'exception' => $containerException
            ],
            'key' => TestClassWithExceptionOnConstruct::class,
        ]);

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf(ContainerException::CONTAINER_ERROR, $id));

        try {
            $container->get('key');
        } catch (ContainerExceptionInterface $e) {
            self::assertNull($e->getPrevious());
            throw $e;
        }
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testContainerConstructExceptionWithNotFoundException(): void
    {
        $containerException = new NotFoundException($id = 'another-key', 13);
        $container = new Container([
            TestClassWithExceptionOnConstruct::class . '::params' => [
                'exception' => $containerException
            ],
            'key' => TestClassWithExceptionOnConstruct::class,
        ]);

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', $id));

        try {
            $container->get('key');
        } catch (ContainerExceptionInterface $e) {
            self::assertNull($e->getPrevious());
            throw $e;
        }
    }
}
