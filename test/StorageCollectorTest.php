<?php declare(strict_types=1);


namespace Container;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class StorageCollectorTest extends TestCase
{

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->reflector = $this->createMock(ReflectorInterface::class);
        $this->parameters = $this->createMock(ParametersInterface::class);
    }

    public function testCollectAndPattern(): void
    {
        $data = ['key' => 'value'];
        $collector = new StorageCollector(storage: $data, reflector: $this->reflector);

        $this->reflector->expects($this->exactly(1))->method('createReflectionClass')
            ->with('Tests\\TestFactory');

        $this->reflector->expects($this->exactly(2))->method('call')
            ->willReturnCallback(function ($fn, string $path) {
                if ($path === '/var/www/tests/*') {
                    return [
                        '/var/www/tests/dirname',
                        '/var/www/tests/another.txt',
                        '/var/www/tests/Test.php',
                        '/var/www/tests/TestFactory.php'
                    ];
                }
                return [
                    '/var/www/tests/dirname/another.txt',
                ];
            });

        $collector->collect('Tests', '/var/www/tests', '/Factory$/');

        self::assertSame($data, $collector->getStorage());
    }

    /**
     * @throws Exception
     */
    public function testCollectInterfaces(): void
    {
        $collector = new StorageCollector(storage: ['PredefinedInterface' => 'PredefinedClass']);

        $interface1 = $this->createReflectionClass(true, 'InterfaceName');
        $interface2 = $this->createReflectionClass(false, 'NotUserDefinedInterfaceName');
        $interface3 = $this->createReflectionClass(true, 'PredefinedInterface');

        $reflector = $this->createMock(ReflectionClass::class);
        $reflector->expects($this->once())->method('getInterfaces')->willReturn([$interface1, $interface2, $interface3]);
        $reflector->expects($this->never())->method('getMethods');
        $reflector->expects($this->atLeastOnce())->method('getName')->willReturn('ClassName');

        $collector->read($reflector, StorageCollector::READ_INTERFACES);

        self::assertSame(['PredefinedInterface' => 'PredefinedClass', 'InterfaceName' => 'ClassName'], $collector->getStorage());
    }

    /**
     * @throws Exception
     */
    public function testCollectConstructors(): void
    {
        $collector = new StorageCollector(storage: ['Predefined' => 'PredefinedValue'], parameters: $this->parameters);

        $method1 = $this->createReflectionMethod(true, 'a constructor name');
        $method2 = $this->createReflectionMethod(true, 'Predefined');
        $method3 = $this->createReflectionMethod(false, 'another constructor name');

        $this->parameters->expects($this->exactly(1))->method('with')
            ->with('ClassName', $method1)
            ->willReturn($this->parameters);

        $reflector = $this->createMock(ReflectionClass::class);
        $reflector->expects($this->never())->method('getInterfaces');
        $reflector->expects($this->once())->method('getMethods')->willReturn([$method1, $method2, $method3]);
        $reflector->expects($this->atLeastOnce())->method('getName')->willReturn('ClassName');

        $collector->read($reflector, StorageCollector::READ_CONSTRUCTORS);

        self::assertSame(['Predefined' => 'PredefinedValue', 'ClassName' => $this->parameters], $collector->getStorage());
    }

    /**
     * @throws Exception
     */
    public function testCollectMethods(): void
    {
        $collector = new StorageCollector(storage: ['ClassName::Predefined' => 'PredefinedValue'], parameters: $this->parameters);

        $method1 = $this->createReflectionMethod(false, 'MyMethodName');
        $method2 = $this->createReflectionMethod(false, 'Predefined');
        $method3 = $this->createReflectionMethod(true, 'ANewOne');

        $this->parameters->expects($this->exactly(1))->method('with')
            ->with('ClassName::MyMethodName', $method1)
            ->willReturn($this->parameters);

        $reflector = $this->createMock(ReflectionClass::class);
        $reflector->expects($this->never())->method('getInterfaces');
        $reflector->expects($this->once())->method('getMethods')->willReturn([$method1, $method2, $method3]);
        $reflector->expects($this->atLeastOnce())->method('getName')->willReturn('ClassName');

        $collector->read($reflector, StorageCollector::READ_METHODS);

        self::assertSame(['ClassName::Predefined' => 'PredefinedValue', 'ClassName::MyMethodName' => $this->parameters], $collector->getStorage());
    }

    /**
     * @throws Exception
     */
    private function createReflectionClass(bool $isUserDefined, string $name): ReflectionClass
    {
        $method = $this->createMock(ReflectionClass::class);
        $method->expects($this->any())->method('isUserDefined')->willReturn($isUserDefined);
        $method->expects($this->any())->method('getName')->willReturn($name);
        return $method;
    }

    /**
     * @throws Exception
     */
    private function createReflectionMethod(bool $isConstructor, string $name): ReflectionMethod
    {
        $method = $this->createMock(ReflectionMethod::class);
        $method->expects($this->any())->method('isConstructor')->willReturn($isConstructor);
        $method->expects($this->any())->method('getName')->willReturn($name);
        return $method;
    }
}