<?php declare(strict_types=1);

namespace Container;

use Closure;
use Container\Exception\ContainerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

class Container implements ContainerInterface
{
    private const INTERFACE_SUFFIX = 'Interface';

    public function __construct(
        private array                     $storage = [],
        private readonly FactoryInterface $factory = new Factory
    )
    {
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->storage)
            || class_exists($id)
            || $this->getClassWithoutInterfaceSuffix($id);
    }

    public function get(string $id, string $alias = null): mixed
    {
        try {
            $storageValue = $this->storage[$alias ?? $id] ?? null;

            if ($this->isStorageValueAnAvailableClass($id, $storageValue)) {
                return $this->get($storageValue, $id);
            }

            if ((is_null($storageValue) || is_null($alias))
                && $className = $this->getClassWithoutInterfaceSuffix($id)) {
                return $this->get($className, $id);
            }

            return $this->process($id, $alias, $storageValue);

        } catch (Throwable $exception) {
            if ($exception instanceof ContainerException === false || $exception->getId() !== ($alias ?? $id)) {
                throw new ContainerException($id, ContainerException::TRANSFORM_EXCEPTION, $exception);
            }
            throw $exception;
        }
    }

    public function set(string $id, string|array|object $value): void
    {
        $this->storage[$id] = $value;
    }

    /**
     * @throws Throwable
     */
    public function createObject(string $class, null|string $alias, array $parameters = []): mixed
    {
        return $this->factory->createObject($class, ...$this->createParameters($class, $alias, $parameters));
    }

    /**
     * @throws Throwable
     */
    public function createParameters(string $class, null|string $alias, array $parameters = []): array
    {
        $constructor = $this->factory->createReflectionMethod($class);

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $parameters[$parameter->getName()] = $this->prepareParameter(
                $constructor, $parameter, $class, $alias,
                $parameters[$parameter->getName()] ?? null,
                array_key_exists($parameter->getName(), $parameters)
            );
        }

        return $parameters;
    }

    /**
     * @throws Throwable
     */
    private function process(string $id, null|string $alias, mixed $storageValue): mixed
    {
        if ($storageValue instanceof Closure) {
            return $this->storage[$alias ?? $id] = $storageValue($this, $id, $alias);
        }

        if (is_object($storageValue)) {
            return $this->storage[$alias ?? $id] = $storageValue;
        }

        if (is_array($storageValue) || is_null($storageValue) || $storageValue === $id) {
            return $this->storage[$alias ?? $id] = $this
                ->createObject($id, $alias, $this->getStorageValueParameters($id, $alias));
        }

        $format = ContainerException::ERROR_WHILE_RETRIEVING_INVALID_STORAGE_VALUE_TYPE;
        throw new ContainerException($alias ?? $id, 0, null,
            sprintf($format, $alias ?? $id, gettype($storageValue)));
    }

    private function getStorageValueParameters(string $className, null|string $classAlias): array
    {
        $parameters = $this->getParametersFromStorage($className . '::__construct');
        $parameters = $this->getParametersFromStorage($className, $parameters);

        if (null === $classAlias) {
            return $parameters;
        }

        $parameters = $this->getParametersFromStorage($classAlias . '::__construct', $parameters);
        return $this->getParametersFromStorage($classAlias, $parameters);
    }

    private function getParametersFromStorage(string $id, array $parameters = []): array
    {
        if (array_key_exists($id, $this->storage) && is_array($this->storage[$id])) {
            return array_merge($parameters, $this->storage[$id]);
        }
        return $parameters;
    }

    /**
     * @throws Throwable
     */
    private function prepareParameter(
        ReflectionMethod    $constructor,
        ReflectionParameter $parameter,
        string              $class,
        null|string         $alias,
        mixed               $value,
        bool                $valueExists
    ): mixed
    {
        if ($value instanceof Closure) {
            $value = $value($this, $class, $alias);
        }
        if ($this->isStorageValueInstanceAvailable($class, $value)) {
            $parameterType = $this->factory->getParameterReflectionNamedType($parameter, $this);
            if (!$parameterType || $parameterType->isBuiltin() === false) {
                return $this->get($this->storage[$value], $value);
            }
        }
        if ($this->isClassAliasInstanceAvailable($class, $value)) {
            return $this->get(substr($value, 0, -9), $value);
        }
        if ($this->isStorageValueTransferable($alias, $value)) {
            return $this->transferStorageValue($class, $alias, $value);
        }
        if (is_null($value) === false || $valueExists) {
            return $value;
        }
        return $this->createParameter(
            $parameter, $constructor, $class,
            $parameterType ?? $this->factory->getParameterReflectionNamedType($parameter, $this)
        );
    }

    private function isStorageValueInstanceAvailable(string $class, mixed $value): bool
    {
        return is_string($value) &&
            $this->isStorageValueAnAvailableClass($class, $this->storage[$value] ?? null);
    }

    private function isClassAliasInstanceAvailable(string $class, mixed $value): bool
    {
        return is_string($value) && str_ends_with($value, self::INTERFACE_SUFFIX)
            && class_exists($className = substr($value, 0, -9))
            && $class !== $className;
    }

    private function isStorageValueAnAvailableClass(string $class, mixed $storageValue): bool
    {
        return is_string($storageValue) && $storageValue !== $class && class_exists($storageValue);
    }

    private function getClassWithoutInterfaceSuffix(string $id): ?string
    {
        return (
            str_ends_with($id, self::INTERFACE_SUFFIX)
            && class_exists($className = substr($id, 0, -9))
        ) ? $className : null;
    }

    private function isStorageValueTransferable(null|string $alias, mixed $value): bool
    {
        if (is_string($value) === false || $alias === $value) {
            return false;
        }
        return is_array($storageValue = $this->storage[$value] ?? null) || is_object($storageValue);
    }

    /**
     * @throws Throwable
     */
    private function transferStorageValue(string $class, null|string $alias, mixed $value): mixed
    {
        $storageValue = $this->storage[$value] ?? null;

        if ($storageValue instanceof Closure) {
            return $storageValue($this, $class, $alias);
        }

        return $storageValue;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createParameter(
        ReflectionParameter      $parameter,
        ReflectionMethod         $constructor,
        string                   $class,
        null|ReflectionNamedType $parameterType
    )
    {
        return $parameter->isOptional() ?
            $this->factory->createOptionalParameterValue($parameter, $this, $parameterType) :
            $this->factory->createRequiredParameterValue($parameter, $this, $parameterType, $constructor, $class);
    }
}
