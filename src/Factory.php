<?php declare(strict_types=1);

namespace Container;

use Container\Exception\ContainerException;
use Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class Factory implements FactoryInterface
{
    public function createObject(string $className, mixed ...$parameters): object
    {
        if (class_exists($className) === false) {
            throw new NotFoundException($className, ContainerException::NOT_FOUND);
        }
        return new $className(...$parameters);
    }

    public function createReflectionMethod(object|string $class, string $method = null): ?ReflectionMethod
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (ReflectionException) {
            throw new NotFoundException($class, ContainerException::NOT_FOUND);
        }
        if (is_null($method)) {
            return $reflector->getConstructor();
        }
        if ($reflector->hasMethod($method)) {
            return $reflector->getMethod($method);
        }
        return null;
    }

    public function getParameterReflectionNamedType(
        ReflectionParameter $parameter,
        ContainerInterface  $container = null
    ): ?ReflectionNamedType
    {
        if ($parameter->hasType() === false) {
            return null;
        }
        $reflectionType = $parameter->getType();
        if ($reflectionType instanceof ReflectionNamedType) {
            return $reflectionType;
        }
        return $this->getParameterReflectionTypeFromList($container, $reflectionType);
    }

    protected function getParameterReflectionTypeFromList(
        null|ContainerInterface                        $container,
        ReflectionUnionType|ReflectionIntersectionType $reflectionType
    ): ?ReflectionNamedType
    {
        $latest = null;
        foreach ($reflectionType->getTypes() as $type) {
            if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
                return $this->getParameterReflectionTypeFromList($container, $type);
            }
            if ($type instanceof ReflectionNamedType &&
                $type->isBuiltin() === false &&
                (!$container || $container->has($type->getName()))
            ) {
                return $type;
            }
            $latest = $type;
        }
        return $latest;
    }

    public function createRequiredParameterValue(
        ReflectionParameter      $parameter,
        null|ContainerInterface  $container,
        null|ReflectionNamedType $type,
        ReflectionMethod         $method,
        string                   $className
    ): mixed
    {
        if (is_null($type) && $parameter->allowsNull()) {
            return null;
        }
        if ($type && $container?->has($type->getName())) {
            return $container->get($type->getName());
        }
        if ($type && $type->allowsNull()) {
            return null;
        }
        $message = sprintf(
            ContainerException::ERROR_WHILE_RETRIEVING_MISSING_PARAMETER,
            $className, $method->getShortName(), $parameter->getName()
        );
        throw new ContainerException($className, ContainerException::GET_REQUIRED_VALUE, null, $message);
    }

    public function createOptionalParameterValue(
        ReflectionParameter      $parameter,
        null|ContainerInterface  $container,
        null|ReflectionNamedType $type
    ): mixed
    {
        try {
            $defaultValue = $parameter->getDefaultValue();
        } catch (ReflectionException) {
            $defaultValue = null;
        }
        if (is_object($defaultValue)) {
            return $defaultValue;
        }
        if ($type && $container?->has($type->getName())) {
            return $container->get($type->getName());
        }
        return $defaultValue;
    }
}
