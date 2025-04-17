<?php declare(strict_types=1);

namespace Container;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;

class Container implements ContainerInterface
{
    public function __construct(protected array $storage = []) {}

    public function get(string $id, null|string $alias = null, null|string $source = null): mixed
    {
        // Check for circular dependency
        if (($source = $source ?? $alias) === $id) {
            throw new ContainerException($alias, ContainerException::CIRCULAR_DEPENDENCY);
        }

        // Return cached value if available
        if ($value = $this->storage[$id] ?? null) {
            return $this->getStorageValue($id, $alias, $source ?? $id, $value);
        }

        // Return container instance when requested
        if ($id === Container::class || $id === ContainerInterface::class) {
            return $this;
        }

        // Create object if class exists
        if (class_exists($id)) {
            return $this->createObject($id, $alias, $source ?? $id);
        }

        // Handle interface resolution
        if (str_ends_with($id, 'Interface') === false) {
            throw new NotFoundException($id, NotFoundException::CLASS_NOT_FOUND);
        }

        // Try to resolve interface to its implementation
        if (class_exists($sub = substr($id, 0, -9)) || isset($this->storage[$sub])) {
            return $this->get($sub, $id, $source ?? $id);
        }

        // Nothing could resolve the id — throw a resolution error
        throw new NotFoundException($id, NotFoundException::INTERFACE_NOT_FOUND);
    }

    public function set(string $id, $value): void
    {
        $this->storage[$id] = $value;
    }

    public function has(string $id): bool
    {
        // Check if the ID is directly available
        if (class_exists($id) ||
            isset($this->storage[$id]) ||
            $id === Container::class ||
            $id === ContainerInterface::class) {
            return true;
        }

        // Check if this is an interface with an available implementation
        return str_ends_with($id, 'Interface')
            && $this->has(substr($id, 0, -9));
    }

    /**
     * Creates a new object instance for the specified class ID.
     * @throws ContainerExceptionInterface
     */
    protected function createObject(string $id, string|null $alias, string $source): object
    {
        try {
            // Get the ReflectionMethod for the requested class constructor
            if ($method = (new ReflectionClass($id))->getConstructor()) {
                return new $id(...$this->getParameters($id, $alias, $source, $method));
            }

            // Class has no constructor, instantiate without parameters
            return new $id;

        } catch (Throwable $exception) {

            // If this is already a container exception, pass it through
            if ($exception instanceof ContainerExceptionInterface) {
                throw $exception;
            }

            // Otherwise, wrap the exception in a ContainerException
            throw new ContainerException($alias ?? $id, ContainerException::CREATE_EXCEPTION, $exception);
        }
    }

    /**
     * Resolves a value from storage, handling Closures and string references.
     *
     * @throws ContainerExceptionInterface
     */
    protected function getStorageValue(string $id, ?string $alias, string $source, mixed $value): mixed
    {
        try {
            // Resolve closure if the stored value is a callback
            if ($value instanceof Closure) {
                $value = $value($this, $id, $alias);
            }

            // Handle string values that reference other container entries
            if (is_string($value) && $this->has($value)) {
                $value = $this->get($value, $id, $source);
            }

            // Cache the resolved value and return it
            return $this->storage[$id] = $value;

        } catch (Throwable $exception) {

            // Rethrow container exceptions directly
            if ($exception instanceof ContainerExceptionInterface) {
                throw $exception;
            }

            // Wrap other exceptions in a container exception
            throw new ContainerException($alias ?? $id, ContainerException::CLOSURE_EXCEPTION, $exception);
        }
    }

    /**
     * @throws Throwable
     */
    public function getParameters(string $id, ?string $alias, string $source, ?ReflectionMethod $method): array
    {
        // Start with any parameters already defined in storage
        $parameters = $this->getStorageParameters($id, $alias);

        // Process each constructor parameter
        foreach ($method?->getParameters() ?? [] as $parameter) {

            // Skip if parameter is already set or is variadic
            if (isset($parameters[$name = $parameter->getName()]) || $parameter->isVariadic()) {
                continue;
            }

            // Resolve and store the parameter value
            $parameters[$name] = $this->getParameterValue($parameter, $id, $alias, $source);
        }

        return $parameters;
    }

    /**
     * Resolves the value for a given constructor parameter.
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function getParameterValue(
        ReflectionParameter $parameter, string $id, string|null $alias, string $source
    ): mixed
    {
        // Check if the parameter has a non-builtin type (like a class or interface)
        if (is_null($reflectionType = $this->getNotBuiltinReflectionNamedType($parameter))) {
            return $this->getDefaultParameterValue($id, $parameter);
        }

        // Try to find a matching entry in the container storage based on the reflection type
        if ($key = $this->getStorageKeyFromReflectionNamedType($parameter, $reflectionType, $id, $alias)) {
            return $this->getStorageValue($key, null, $source, $this->storage[$key]);
        }

        // Use the default value if it's available
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // If the container has the type, resolve it (but prevent recursion, circular dependency)
        if ($this->has($reflectionType->getName())) {
            if ($reflectionType->getName() === $id) {
                throw new ContainerException("$id::{$parameter->getName()}", ContainerException::PARAMETER_DEPENDENCY);
            }
            return $this->get($reflectionType->getName(), $alias ?? $id, $source);
        }

        // Return null if type permits it
        if ($parameter->allowsNull()) {
            return null;
        }

        // Nothing could resolve the parameter — throw a resolution error
        throw new NotFoundException("$id::\${$parameter->getName()}", NotFoundException::BUILTIN_NOT_FOUND);
    }

    /**
     * Retrieves the storage parameters for a given ID with optional alias support.
     */
    protected function getStorageParameters(string $id, string|null $alias): array
    {
        // First check if we have parameters stored under the alias
        if ($alias && is_array($this->storage["$alias::params"] ?? null)) {
            return $this->storage["$alias::params"];
        }

        // If no alias parameters found, try using the original ID
        if (is_array($this->storage["$id::params"] ?? null)) {
            return $this->storage["$id::params"];
        }

        // Return empty array if no parameters found
        return [];
    }

    /**
     * Gets a non-builtin ReflectionNamedType from a parameter, if available.
     */
    protected function getNotBuiltinReflectionNamedType(ReflectionParameter $parameter): null|ReflectionNamedType
    {
        // If parameter has no type or type is null, return null
        if ($parameter->hasType() === false || is_null($reflectionType = $parameter->getType())) {
            return null;
        }

        // If type is a union or intersection type, try to extract a usable named type from it
        if ($reflectionType instanceof ReflectionUnionType ||
            $reflectionType instanceof ReflectionIntersectionType) {
            $reflectionType = $this->getNamedTypeFromReflectionGroup($reflectionType);
        }

        // If we have a named type that's not a builtin, return it
        if ($reflectionType instanceof ReflectionNamedType) {
            return $reflectionType->isBuiltin() ? null : $reflectionType;
        }

        // Otherwise return null
        return null;
    }

    /**
     * Recursively searches for a usable ReflectionNamedType in a union or intersection type.
     */
    protected function getNamedTypeFromReflectionGroup(
        ReflectionUnionType|ReflectionIntersectionType $reflectionType
    ): null|ReflectionNamedType
    {
        // Iterate through each type in the union/intersection
        foreach ($reflectionType->getTypes() as $type) {

            // If we encounter another union/intersection, recursively search it
            if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
                $type = $this->getNamedTypeFromReflectionGroup($type);
            }

            // If we found a named type that exists in the container, return it
            if ($type instanceof ReflectionNamedType && $this->has($type->getName())) {
                return $type;
            }
        }

        // If no usable named type was found, return null
        return null;
    }

    /**
     * Gets the default value for a parameter.
     * @throws NotFoundException
     */
    protected function getDefaultParameterValue(string $id, ReflectionParameter $parameter): mixed
    {
        // If parameter has a default value, return it
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // If parameter allows null, return null as default
        if ($parameter->allowsNull()) {
            return null;
        }

        // Otherwise throw an exception indicating no default value was found
        throw new NotFoundException("$id::\${$parameter->getName()}", NotFoundException::DEFAULT_NOT_FOUND);
    }

    protected function getStorageKeyFromReflectionNamedType(
        ReflectionParameter $parameter, ReflectionNamedType $reflectionType, string $id, string|null $alias
    ): null|string
    {
        // Check if the parameter name, with alias exists directly in storage
        if ($alias && isset($this->storage[$key = "$alias::{$parameter->getName()}"])) {
            return $key;
        }

        // Check if the parameter name, with id exists directly in storage
        if (isset($this->storage[$key = "$id::{$parameter->getName()}"])) {
            return $key;
        }

        // Check if the type name, with alias exists directly in storage
        if ($alias && isset($this->storage[$key = "$alias::{$reflectionType->getName()}"])) {
            return $key;
        }

        // Check if the type name, with id exists directly in storage
        if (isset($this->storage[$key = "$id::{$reflectionType->getName()}"])) {
            return $key;
        }

        // Check if the type name, with parameter name exists directly in storage
        if (isset($this->storage[$key = "{$reflectionType->getName()}::{$parameter->getName()}"])) {
            return $key;
        }

        // Check if the type name exists directly in storage
        if (isset($this->storage[$key = $reflectionType->getName()])) {
            return $key;
        }

        // Check if the type name ends with 'Interface' and the corresponding class exists in storage
        if (str_ends_with($key, 'Interface') && isset($this->storage[$sub = substr($key, 0, -9)])) {
            return $sub;
        }

        // If no storage key was found, return null
        return null;
    }
}
