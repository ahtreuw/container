<?php declare(strict_types=1);

namespace Vulpes\Container;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use Vulpes\Container\Parameter\ObjParam;
use Vulpes\Container\Parameter\ValParam;

class Factory implements FactoryInterface
{
    public function createInstance(string $className, mixed ...$parameters): object
    {
        return new $className(...$parameters);
    }

    public function invokeClosure(Closure $closure, mixed ...$parameters): mixed
    {
        return $closure(...$parameters);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createParameters(string $id, string $class): Parameters
    {
        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (ReflectionException) {
            throw new NotFoundException(sprintf('No entry was found for %s identifier.', $id), NotFoundException::CREATE_PARAMETERS);
        }

        if (is_null($constructor = $reflectionClass->getConstructor())) {
            return new Parameters($id ?: $class, $class);
        }

        $parameters = [];
        foreach ($constructor->getParameters() as $reflectionParameter) {
            $reflectionType = $reflectionParameter->hasType() ? $reflectionParameter->getType() : null;
            $parameters[] = $this->createParameter($reflectionParameter, $reflectionType);
        }
        return new Parameters($id ?: $class, $class, ...$parameters);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function createParameter(
        ReflectionParameter $reflectionParameter,
        null|ReflectionType $reflectionType
    ): Parameter
    {
        if (is_null($reflectionType)) {
            return $this->createParameterWithoutType($reflectionParameter);
        }

        if ($reflectionType instanceof ReflectionNamedType) {
            if ($reflectionType->isBuiltin()) {
                return $this->createParameterWithoutType($reflectionParameter);
            }
            return new ObjParam($reflectionType->getName());
        }

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        foreach ($reflectionType->getTypes() as $type) {
            try {
                return $this->createParameter($reflectionParameter, $type);
            } catch (ContainerExceptionInterface) { // nothing here...
            }
        }
        throw new ContainerException(sprintf('Error while create the parameter %s.', $reflectionParameter->getName()), ContainerException::BUILTIN);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function createParameterWithoutType(ReflectionParameter $reflectionParameter): Parameter
    {
        if ($reflectionParameter->isDefaultValueAvailable()) {
            return new ValParam($reflectionParameter->getDefaultValue());
        }
        if ($reflectionParameter->allowsNull()) {
            return new ValParam(null);
        }
        throw new ContainerException(sprintf('Error while create the parameter %s.', $reflectionParameter->getName()), ContainerException::BUILTIN);
    }
}
