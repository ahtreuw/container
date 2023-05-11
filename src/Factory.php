<?php declare(strict_types=1);

namespace Container;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

class Factory implements FactoryInterface
{
    private FactoryInterface $factory;

    public function __construct(FactoryInterface $factory = null)
    {
        $this->factory = $factory instanceof FactoryInterface ? $factory : $this;
    }

    public function createObject(string $class, mixed ...$parameters): object
    {
        return new $class(...$parameters);
    }

    public function call(callable $callable, mixed ...$parameters): mixed
    {
        return $callable(...$parameters);
    }

    public function createReflectionMethod(object|string $objectOrMethod, string|null $method = null): null|ReflectionMethod
    {
        /** @var null|ReflectionMethod $reflectionMethod */
        try {
            $reflectionMethod = $this->factory->createObject(ReflectionMethod::class, $objectOrMethod, $method);
        } catch (ReflectionException|Throwable) {
            $reflectionMethod = $this->createReflectionClass($objectOrMethod)?->getConstructor();
        }
        return $reflectionMethod;
    }

    public function createReflectionClass(object|string $objectOrClass): null|ReflectionClass
    {
        /** @var null|ReflectionClass $reflectionClass */
        $reflectionClass = null;
        try {
            $reflectionClass = $this->factory->createObject(ReflectionClass::class, $objectOrClass);
        } catch (ReflectionException|Throwable) {
        }
        return $reflectionClass;
    }
}
