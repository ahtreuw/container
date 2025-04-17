<?php declare(strict_types=1);

namespace Tests\Container;

use Container\Container;
use Container\ContainerException;
use Container\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ContainerExceptionsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        eval('
        namespace Tests\\Container\\ContainerExceptionsTest\\TestObjects; 
        
        interface TestClassInterface {}
        interface TestAliasClassInterface {}
        
        class TestClass implements TestClassInterface
        {
            public function __construct(TestAliasClassInterface $alias) {}
        }
        class TestAliasClass implements TestAliasClassInterface
        {
            public function __construct(TestClassInterface $alias) {}
        }
        class TestClassWithExceptionOnConstruct
        {
            public function __construct(\\Throwable $exception) { throw $exception; }
        }
        class TestClassWithRecursiveParameter
        {
            public function __construct(TestClassWithRecursiveParameter $value) {}
        }');
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testGetWithSourceRecursion(): void
    {
        $container = new Container;

        $id = 'Tests\Container\ContainerExceptionsTest\TestObjects\TestAliasClassInterface';

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf(ContainerException::CONTAINER_ERROR, $id));
        self::expectExceptionMessage(ContainerException::CIRCULAR_DEPENDENCY_MESSAGE);

        $container->get($id);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testGetWithRecursiveParameter(): void
    {
        $id = 'Tests\Container\ContainerExceptionsTest\TestObjects\TestClassWithRecursiveParameter';
        $container = new Container;

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf(ContainerException::CONTAINER_ERROR, $id));
        self::expectExceptionMessage(ContainerException::PARAMETER_DEPENDENCY_MESSAGE);

        $container->get($id);
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
        $id = 'Tests\Container\ContainerExceptionsTest\TestObjects\TestClassWithExceptionOnConstruct';
        $runtimeException = new RuntimeException('My runtime error message', 13);
        $container = new Container([
            "$id::params" => [
                'exception' => $runtimeException
            ],
            'key' => $id,
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
        $id = 'Tests\Container\ContainerExceptionsTest\TestObjects\TestClassWithExceptionOnConstruct';
        $containerException = new ContainerException($keyId = 'another-key', 13);
        $container = new Container([
            "$id::params" => [
                'exception' => $containerException
            ],
            'key' => $id,
        ]);

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf(ContainerException::CONTAINER_ERROR, $keyId));

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
        $id = 'Tests\Container\ContainerExceptionsTest\TestObjects\TestClassWithExceptionOnConstruct';
        $containerException = new NotFoundException($keyId = 'another-key', 13);
        $container = new Container([
            "$id::params" => [
                'exception' => $containerException
            ],
            'key' => $id,
        ]);

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', $keyId));

        try {
            $container->get('key');
        } catch (ContainerExceptionInterface $e) {
            self::assertNull($e->getPrevious());
            throw $e;
        }
    }
}
