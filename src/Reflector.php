<?php declare(strict_types=1);

namespace Container;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Reflector implements ReflectorInterface
{
    private ReflectorInterface $reflector;

    public function __construct(ReflectorInterface $reflector = null)
    {
        $this->reflector = $reflector instanceof ReflectorInterface ? $reflector : $this;
    }

    public function create(string $class, mixed ...$parameters): object
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
            $reflectionMethod = $this->reflector->create(ReflectionMethod::class, $objectOrMethod, $method);
        } catch (ReflectionException) {
            $reflectionMethod = $this->createReflectionClass($objectOrMethod)?->getConstructor();
        }
        return $reflectionMethod;
    }

    public function createReflectionClass(object|string $objectOrClass): null|ReflectionClass
    {
        /** @var null|ReflectionClass $reflectionClass */
        $reflectionClass = null;
        try {
            $reflectionClass = $this->reflector->create(ReflectionClass::class, $objectOrClass);
        } catch (ReflectionException) {
        }
        return $reflectionClass;
    }
}
