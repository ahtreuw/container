<?php declare(strict_types=1);

namespace Container\Test;

use ArrayAccess;
use ArrayObject;
use Container\Container;
use Container\Exception\ContainerException;
use Container\Exception\NotFoundException;
use Container\Factory;
use Container\FactoryInterface;
use DateTime;
use DateTimeZone;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ContainerTest extends TestCase
{

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testCreateObject(): void
    {
        $container = new Container(storage: [
            'id-01' => Factory::class,
            'id-02' => FactoryInterface::class,
            'id-03' => Container::class,
            'id-03::construct' => ['factory' => 'id-02'],
            Container::class => ['storage' => 'storage'],
            'storage' => ['test' => Factory::class],
            'id-04' => function () {
                return new Factory;
            }
        ]);

        self::assertInstanceOf(FactoryInterface::class, $container->get(Factory::class));
        self::assertInstanceOf(FactoryInterface::class, $container->get(FactoryInterface::class));
        self::assertInstanceOf(FactoryInterface::class, $container->get('id-01'));
        self::assertInstanceOf(Container::class, $new = $container->get('id-03'));

        self::assertInstanceOf(FactoryInterface::class, $new->get('test'));

        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(
            sprintf(ContainerException::ERROR_WHILE_RETRIEVING_INVALID_STORAGE_VALUE_TYPE, "id-02", "string")
        );

        $container->get('id-02');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testCreateObjectClosure(): void
    {
        $container = new Container(storage: [
            ContainerInterface::class => Container::class,
            Container::class => ['factory' => 'id-01', 'storage' => function () {
                return [];
            }],
            'id-01' => function () {
                return new Factory;
            },
            FactoryInterface::class => function () {
                return new Factory;
            }
        ]);
        self::assertInstanceOf(ContainerInterface::class, $container->get(ContainerInterface::class));
        self::assertInstanceOf(FactoryInterface::class, $container->get(FactoryInterface::class));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testCreateObjectWithStringHasAlias(): void
    {
        $container = new Container(storage: [
            DateTime::class => ['datetime' => 'now', 'timezone' => 'timezone'],
            'now' => DateTimeZone::class, // do not use this on string type
            'timezone' => new DateTimeZone('UTC')
        ]);
        self::assertInstanceOf(DateTime::class, $container->get(DateTime::class));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testBaseOfAlias(): void
    {
        $container = new Container([
            Container::class => ['factory' => FactoryInterface::class],
            'aaa' => Container::class,
        ]);
        self::assertInstanceOf(Container::class, $container->get('aaa'));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testCreateObjectFromArray(): void
    {
        $this->runTestCOFA(ArrayObject::class, ArrayObject::class);
        $this->runTestCOFA(ArrayObject::class, ArrayObject::class . '::__construct');

        $this->runTestCOFA(ArrayObject::class . 'Interface', ArrayObject::class);
        $this->runTestCOFA(ArrayObject::class . 'Interface', ArrayObject::class . '::__construct');

        $this->runTestCOFA(ArrayObject::class . 'Interface', ArrayObject::class . 'Interface');
        $this->runTestCOFA(ArrayObject::class . 'Interface', ArrayObject::class . 'Interface::__construct');

        $extra = [ArrayAccess::class => ArrayObject::class];

        $this->runTestCOFA(ArrayAccess::class, ArrayObject::class, $extra);
        $this->runTestCOFA(ArrayAccess::class, ArrayObject::class . '::__construct', $extra);
        $this->runTestCOFA(ArrayAccess::class, ArrayAccess::class . '::__construct', $extra);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runTestCOFA(string $id, string $key, array $extra = []): void
    {
        $container = new Container(storage: array_merge([$key => ['array' => ['key' => 'value']]], $extra));

        $result = $container->get($id);

        self::assertInstanceOf(ArrayObject::class, $result);
        self::assertEquals('value', $container->get($id)['key']);
    }

    public static function getInValidStorageValues(): array
    {
        return [[13], ['a string value'], [13.13], [true], [false]];
    }

    /**
     * @dataProvider getInValidStorageValues
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testInValidStorageValue(int|string|float|null|bool $storageValue): void
    {
        $container = new Container([$id = 'key' => $storageValue]);

        self::expectException(ContainerExceptionInterface::class);

        self::expectExceptionMessage(
            sprintf(ContainerException::ERROR_WHILE_RETRIEVING_INVALID_STORAGE_VALUE_TYPE,
                $id, gettype($storageValue)));

        $container->get($id);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function testClosureExceptionInterface(): void
    {
        $container = new Container;
        $container->set('exceptionThrowerEntity', function () {
            throw new Exception;
        });
        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(
            sprintf(ContainerException::ERROR_WHILE_RETRIEVING_THE_ENTRY, 'exceptionThrowerEntity')
        );
        $container->get('exceptionThrowerEntity');
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testNotFoundExceptionInterface(): void
    {
        $container = new Container;
        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(
            sprintf(NotFoundException::NO_ENTRY_WAS_FOUND_FOR_X_IDENTIFIER, 'notExistingEntity')
        );
        $container->get('notExistingEntity');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testFactoryExceptionInterface(): void
    {
        $factory = $this->createMock(FactoryInterface::class);
        $factory->expects($this->once())->method('createReflectionMethod')
            ->willReturnCallback(function () {
                throw new Exception;
            });

        $container = new Container(factory: $factory);
        self::expectException(ContainerException::class);
        self::expectExceptionMessage(
            sprintf(ContainerException::ERROR_WHILE_RETRIEVING_THE_ENTRY, 'notExistingEntity')
        );
        $container->get('notExistingEntity');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorParameterFromCustomKey(): void
    {
        $container = new Container([
            'my-message' => 'another-message', // NOPE, with this there is no different instances when needed
            'custom-key' => ContainerException::class,
            'previous' => Exception::class,
            ContainerException::class => $params = [
                'id' => 'my-id', 'code' => 13, 'previous' => 'previous', 'message' => 'my-message'
            ]
        ]);

        /** @var ContainerException $exception */
        $exception = $container->get('custom-key');

        self::assertInstanceOf(ContainerException::class, $exception);

        self::assertEquals($params['code'], $exception->getCode());
        self::assertInstanceOf(Exception::class, $exception->getPrevious());
        self::assertEquals($params['message'], $exception->getMessage());
    }


    /**
     * @throws Throwable
     */
    public function testOptionalParameters(): void
    {
        $object = new class {
            const TEST = true;

            public function __construct(
                public null|ContainerInterface              $container00 = null,
                public null|ContainerInterface              $container01 = null,
                public null|ContainerInterface|MockObject   $container11 = null,
                public null|MockObject|ContainerInterface   $container12 = null,
                public ContainerInterface                   $container21 = new Container,
                public ContainerInterface|MockObject        $container31 = new Container,
                public MockObject|ContainerInterface        $container32 = new Container,
                public null|(ContainerInterface&MockObject) $container41 = null,
                public null|(MockObject&ContainerInterface) $container42 = null,
                public null|string|array                    $container101 = null,
                public                                      $container102 = [null],
                public                                      $container103 = self::TEST
            )
            {
            }
        };

        $className = get_class($object);

        $container = new Container(storage: [
            ContainerInterface::class => Container::class
        ], factory: new Factory);

        $parameters = $container->createParameters($className, null, [
            'container00' => null
        ]);

        self::assertIsArray($parameters);

        self::assertNull($parameters['container00']);
        self::assertInstanceOf(Container::class, $parameters['container01']);
        self::assertInstanceOf(Container::class, $parameters['container11']);
        self::assertInstanceOf(Container::class, $parameters['container12']);

        self::assertInstanceOf(Container::class, $parameters['container21']);
        self::assertInstanceOf(Container::class, $parameters['container31']);
        self::assertInstanceOf(Container::class, $parameters['container32']);

        self::assertInstanceOf(Container::class, $parameters['container41']);
        self::assertInstanceOf(Container::class, $parameters['container42']);

        self::assertNull($parameters['container101']);
        self::assertEquals([null], $parameters['container102']);
        self::assertEquals($object::TEST, $parameters['container103']);
    }

    /**
     * @throws Throwable
     */
    public function testRequiredParameters(): void
    {
        $container = new Container([ContainerInterface::class => Container::class], new Factory);

        $object = new class (null,null, null, null, $container, $container, $container, null, null, null, null, null) {
            public function __construct(
                public null|ContainerInterface              $container00,
                public null|ContainerInterface              $container01,
                public null|ContainerInterface|MockObject   $container11,
                public null|MockObject|ContainerInterface   $container12,
                public ContainerInterface                   $container21,
                public ContainerInterface|MockObject        $container31,
                public MockObject|ContainerInterface        $container32,
                public null|(ContainerInterface&MockObject) $container41,
                public null|(MockObject&ContainerInterface) $container42,
                public null|string|array                    $container101,
                public                                      $container102,
                public                                      $container201
            )
            {
            }
        };

        $parameters = $container->createParameters(get_class($object), null, [
            'container00' => null,
            'container201' => $customValue = 'my-customValue',
        ]);

        self::assertIsArray($parameters);

        self::assertNull($parameters['container00']);
        self::assertInstanceOf(ContainerInterface::class, $c = $parameters['container01']);
        self::assertSame($c, $parameters['container11']);
        self::assertSame($c, $parameters['container12']);

        self::assertSame($c, $parameters['container21']);
        self::assertSame($c, $parameters['container31']);
        self::assertSame($c, $parameters['container32']);

        self::assertSame($c, $parameters['container41']); // odd one out 1
        self::assertSame($c, $parameters['container42']); // odd one out 2

        self::assertNull($parameters['container101']);
        self::assertNull($parameters['container102']);
        self::assertNull($parameters['container103']);
        self::assertEquals($customValue, $parameters['container201']);
    }

    /**
     * @throws Throwable
     */
    public function testRequiredParameters2(): void
    {
        $container = new Container([], new Factory);

        $object = new class (null, null, null, $container, $container, $container, null, null, null, null, null) {
            public function __construct(
                public null|Container              $container01,
                public null|Container|MockObject   $container11,
                public null|MockObject|Container   $container12,
                public Container                   $container21,
                public Container|MockObject        $container31,
                public MockObject|Container        $container32,
                public null|(Container&MockObject) $container41,
                public null|(MockObject&Container) $container42,
                public null|string|array           $container101,
                public                             $container102,
                public                             $container201
            )
            {
            }
        };

        $parameters = $container->createParameters(get_class($object), null, [
            'container201' => $customValue = 'my-customValue'
        ]);

        self::assertIsArray($parameters);

        self::assertInstanceOf(ContainerInterface::class, $c = $parameters['container01']);
        self::assertSame($c, $parameters['container11']);
        self::assertSame($c, $parameters['container12']);

        self::assertSame($c, $parameters['container21']);
        self::assertSame($c, $parameters['container31']);
        self::assertSame($c, $parameters['container32']);

        self::assertSame($c, $parameters['container41']); // odd one out 1
        self::assertSame($c, $parameters['container42']); // odd one out 2

        self::assertNull($parameters['container101']);
        self::assertNull($parameters['container102']);
        self::assertNull($parameters['container103']);
        self::assertEquals($customValue, $parameters['container201']);
    }

}
