<?php declare(strict_types=1);

namespace Vulpes\Container;

use Exception;
use JetBrains\PhpStorm\Pure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use stdClass;
use Vulpes\Container\Parameter\ArgParam;
use Vulpes\Container\Parameter\EnvParam;
use Vulpes\Container\Parameter\ObjParam;
use Vulpes\Container\Parameter\ValParam;

class ContainerTest extends TestCase
{
    private ContainerInterface $container;
    private FactoryInterface|MockObject $factory;
    private StorageInterface|MockObject $storage;
    private CacheInterface|MockObject $cache;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->factory = $this->createMock(FactoryInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->container = new Container(
            $this->factory,
            $this->storage,
            $this->cache
        );
    }

    /**
     * @dataProvider hasProvider
     */
    public function testHas(string $id, bool $hasValue, bool $expected): void
    {
        $this->storage->expects($this->any())->method('has')->willReturn($hasValue);

        $result = $this->container->has($id);

        self::assertEquals($expected, $result);
    }

    public function hasProvider(): array
    {
        return [
            ['key', true, true],
            ['key', false, false],
            [FactoryInterface::class, false, true],
            [Factory::class, false, true],
        ];
    }

    public function testSet(string $key = 'key', mixed $value = 'value'): void
    {
        $this->storage->expects($this->once())->method('set')->with($key, $value);
        $this->container->set($key, $value);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetInterface(): void
    {
        $this->factory->expects($this->once())->method('createParameters')
            ->with(Factory::class, Factory::class)
            ->willReturn(new Parameters(Factory::class, Factory::class));

        $this->container->get(FactoryInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetAlias(): void
    {
        $object = new stdClass;
        $this->storage->expects($this->any())->method('has')->willReturnCallback(function ($id) {
            return $id === 'any';
        });
        $this->storage->expects($this->once())->method('get')->with('any')->willReturn($object::class);
        $this->factory->expects($this->once())->method('createInstance')->with($object::class)->willReturn($object);
        $this->factory->expects($this->once())->method('createParameters')->willReturn(new Parameters($object::class, $object::class));

        $this->storage->expects($this->once())->method('set')->with('any', $object);

        $result = $this->container->get('any');

        self::assertSame($object, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetDefinition(): void
    {
        $object = new stdClass;
        $this->storage->expects($this->any())->method('has')->willReturnCallback(function ($id) {
            return $id === 'key' || $id === 'definition';
        });
        $this->storage->expects($this->exactly(2))->method('get')
            ->willReturnOnConsecutiveCalls('definition', $object);
        $this->storage->expects($this->once())->method('set')->with('key', $object);

        $result = $this->container->get('key');

        self::assertSame($object, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @dataProvider getStorageValue
     */
    public function testGetStorageValue(string $id, mixed $value): void
    {
        $this->storage->expects($this->atLeastOnce())->method('has')->willReturnOnConsecutiveCalls(true, false);
        $this->storage->expects($this->once())->method('get')->with($id)->willReturn($value);

        $result = $this->container->get($id);

        self::assertSame($value, $result);
    }

    #[Pure] public function getStorageValue(): array
    {
        return [
            ['key00', 13],
            ['key01', 'just an avg value'],
            ['key02', ['another value', '']],
            ['key03', new stdClass],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetStorageObjectParameters(string $id = 'test-class'): void
    {
        $std = new stdClass();
        putenv('TEST_ENV=TEST_VAL');

        $object = new Parameters('test-class', 'test-class',
            new EnvParam('TEST_ENV'),
            new ArgParam('ArgParam:key'),
            new ObjParam('ObjParam:key'),
            new ValParam('ValParam:val'),
            $this->createMock(Parameter::class)
        );

        $this->storage->expects($this->any())->method('has')->willReturn(true);
        $this->storage->expects($this->any())->method('get')->willReturnCallback(function ($id) use ($object, $std) {
            return match ($id) {
                'test-class' => $object,
                'ArgParam:key' => ['ArgParam:val'],
                'ObjParam:key' => $std
            };
        });

        $this->factory->expects($this->once())->method('createInstance')->with(
            $id, 'TEST_VAL', ['ArgParam:val'], $std, 'ValParam:val', null
        );

        $this->factory->expects($this->any())->method('createParameters')->willReturn(new Parameters($id, $id));

        $this->container->get($id);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetClosure(string $id = 'test-class'): void
    {
        $object = new stdClass;
        $closure = function () {
        };

        $this->storage->expects($this->once())->method('has')->with($id)->willReturn(true);
        $this->storage->expects($this->once())->method('get')->with($id)->willReturn($closure);
        $this->storage->expects($this->once())->method('set')->with($id, $object);

        $this->factory->expects($this->once())->method('invokeClosure')
            ->with($closure, $this->container, $id)->willReturn($object);

        $result = $this->container->get($id);

        self::assertSame($object, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function testGetNotFoundException(): void
    {
        self::expectException(NotFoundExceptionInterface::class);
        self::expectException(ContainerExceptionInterface::class);
        $this->container->get('not-existing-classname');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function testGetHandleException(): void
    {
        $this->storage->expects($this->once())->method('has')->willReturnCallback(function () {
            throw new Exception;
        });
        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionCode(ContainerException::GET);
        $this->container->get('id');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function testGetClosureException(string $id = 'test-class'): void
    {
        $this->storage->expects($this->once())->method('has')->willReturn(true);
        $this->storage->expects($this->once())->method('get')->willReturn(function () {
        });

        $this->factory->expects($this->once())->method('invokeClosure')->willReturnCallback(function () {
            throw new Exception;
        });

        self::expectException(ContainerExceptionInterface::class);

        $this->container->get($id);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function testGetClosureContainerException(string $id = 'test-class'): void
    {
        $this->storage->expects($this->once())->method('has')->willReturn(true);
        $this->storage->expects($this->once())->method('get')->willReturn(function () {
        });

        $this->factory->expects($this->once())->method('invokeClosure')->willReturnCallback(function () {
            throw new ContainerException;
        });

        self::expectExceptionCode(ContainerException::INVOKE);
        self::expectException(ContainerExceptionInterface::class);

        $this->container->get($id);
    }

    public function testCacheParamsFromReflector(string $id = 'stdClass'): void
    {
        $this->cache->expects($this->never())->method('set');
        $this->cache->expects($this->once())->method('has')->with('params:' . $id)->willReturn(true);
        $this->cache->expects($this->once())->method('get')->with('params:' . $id)->willReturn(new Parameters($id, $id));

        $this->container->get($id);
    }

    public function testWithoutCache(string $id = 'stdClass'): void
    {
        $this->cache->expects($this->never())->method('set');
        $this->cache->expects($this->never())->method('has');
        $this->cache->expects($this->never())->method('get');

        $this->container = new Container($this->factory, $this->storage);
        $this->container->get($id);
    }
}
