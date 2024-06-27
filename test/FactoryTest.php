<?php declare(strict_types=1);

namespace Container\Test;

use Container\Exception\ContainerException;
use Container\Exception\NotFoundException;
use Container\Factory;
use Container\FactoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use stdClass;
use Throwable;

class FactoryTest extends TestCase
{
    private FactoryInterface $factory;

    protected function setUp(): void
    {
        $this->factory = new Factory;
    }

    /**
     * @throws Throwable
     */
    public function testCreateObject(): void
    {
        $stdClass = $this->factory->createObject(stdClass::class);
        self::assertInstanceOf(stdClass::class, $stdClass);

        $date = $this->factory->createObject(DateTimeImmutable::class, $dt = '2024-01-01 12:00:00');
        self::assertInstanceOf(DateTimeImmutable::class, $date);
        self::assertEquals($dt, $date->format('Y-m-d H:i:s'));

        self::expectException(NotFoundExceptionInterface::class);
        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(
            sprintf(NotFoundException::NO_ENTRY_WAS_FOUND_FOR_X_IDENTIFIER, "NotExistingClassName")
        );

        $this->factory->createObject('NotExistingClassName');
    }

    /**
     * @throws Throwable
     */
    public function testCreateReflectionMethod(): void
    {
        $std = $this->factory->createReflectionMethod(stdClass::class);
        self::assertNull($std);

        $date = $this->factory->createReflectionMethod(DateTimeImmutable::class);
        self::assertInstanceOf(ReflectionMethod::class, $date);
        self::assertTrue($date->isConstructor());

        $dateAdd = $this->factory->createReflectionMethod(DateTimeImmutable::class, 'add');
        self::assertInstanceOf(ReflectionMethod::class, $dateAdd);
        self::assertEquals(DateTimeImmutable::class, $dateAdd->getDeclaringClass()->getName());
        self::assertEquals('add', $dateAdd->getShortName());
        self::assertTrue($dateAdd->isConstructor() === false);

        $dateNotExistingMethod = $this->factory->createReflectionMethod(DateTimeImmutable::class, 'notExistingMethod');
        self::assertNull($dateNotExistingMethod);

        self::expectException(NotFoundExceptionInterface::class);
        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(
            sprintf(NotFoundException::NO_ENTRY_WAS_FOUND_FOR_X_IDENTIFIER, "NotExistingClassName")
        );

        $this->factory->createReflectionMethod('NotExistingClassName');
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testCreateRequiredParameterValueException(): void
    {
        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionMessage(
            sprintf(ContainerException::ERROR_WHILE_RETRIEVING_MISSING_PARAMETER,
                'className', 'myMethodName', 'myParamName'));

        $parameter = $this->createMock(ReflectionParameter::class);
        $method = $this->createMock(ReflectionMethod::class);

        $parameter->expects($this->once())->method('getName')->willReturn('myParamName');
        $method->expects($this->once())->method('getShortName')->willReturn('myMethodName');

        $this->factory->createRequiredParameterValue($parameter, null, null, $method, 'className');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testCreateOptionalParameterValueException(): void
    {
        $parameter = $this->createMock(ReflectionParameter::class);
        $parameter->expects($this->once())->method('getDefaultValue')->willReturnCallback(function () {
            throw new ReflectionException;
        });
        self::assertNull($this->factory->createOptionalParameterValue($parameter, null, null));
    }

}
