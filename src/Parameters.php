<?php declare(strict_types=1);

namespace Container;

use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class Parameters implements ParametersInterface
{
    private array $parameters;
    private bool $constructor;

    public function isConstructor(): bool
    {
        return $this->constructor;
    }

    public function make(ContainerInterface $container, mixed ...$arguments): array
    {
        $return = [];

        foreach ($arguments as $name => $value) {
            if (array_key_exists($name, $this->parameters) === false) {
                continue;
            }

            [$type, $isBuiltIn, $optional, $defaultValue, $selfBuild] = $this->parameters[$name];

            $return[$name] = $isBuiltIn ? $this->prepareBuiltInValue($type, $value) : $value;

            unset($arguments[$name]);
        }

        foreach ($this->parameters as $name => $values) {
            if (array_key_exists($name, $return)) {
                continue;
            }

            [$type, $isBuiltIn, $optional, $defaultValue, $selfBuild] = $values;

            if (is_null($defaultValue) && $selfBuild) {
                continue;
            }

            if ($isBuiltIn) {
                $value = $this->prepareBuiltInValue($type, array_shift($arguments));
                $return[$name] = is_null($value) ? $defaultValue : $value;
                continue;
            }

            if ($type && $container->has($type)) {
                $return[$name] = $container->get($type);
                continue;
            }

            if ($optional) {
                continue;
            }

            $return[$name] = array_shift($arguments);
        }

        return $return;
    }

    private function prepareBuiltInValue(null|string $type, mixed $value): mixed
    {
        return match ($type) {
            'bool', 'boolean' => boolval($value),
            'int', 'integer' => intval($value),
            'float', 'double' => floatval($value),
            'string', 'mixed' => strval($value),
            default => $value
        };
    }

    public function with(string $id, null|ReflectionMethod $reflectionMethod): ParametersInterface
    {
        $new = clone $this;

        $new->constructor = $reflectionMethod?->isConstructor() ??
            str_contains($id, '::__construct') || str_contains($id, '::') === false;

        $new->parameters = [];
        foreach ($reflectionMethod?->getParameters() ?? [] as $parameter) {
            $new->parameters[$parameter->getName()] = [
                $this->getParameterType($parameter),
                $this->isParameterBuiltIn($parameter),
                $parameter->isOptional(),
                $this->getParameterDefaultValue($parameter),
                $this->hasParameterSelfBuild($parameter),
            ];
        }

        return $new;
    }

    private function getParameterType(ReflectionParameter $parameter): null|string
    {
        if ($parameter->hasType() === false) {
            return null;
        }
        if (($reflectionType = $parameter->getType()) instanceof ReflectionNamedType) {
            return $reflectionType->getName();
        }
        $latest = null;
        foreach ($reflectionType->getTypes() as $type) {
            if (($latest = $type)->isBuiltin() === false) {
                return $type->getName();
            }
        }
        return $latest?->getName();
    }

    private function isParameterBuiltIn(ReflectionParameter $parameter): bool
    {
        if ($parameter->hasType() === false) {
            return true;
        }
        if (($reflectionType = $parameter->getType()) instanceof ReflectionNamedType) {
            if ($reflectionType->isBuiltin()) {
                return true;
            }
            return false;
        }
        foreach ($reflectionType->getTypes() as $type) {
            if ($type->isBuiltin() === false) {
                return false;
            }
        }
        return true;
    }

    private function getParameterDefaultValue(ReflectionParameter $parameter): mixed
    {
        try {
            if ($parameter->hasType() === false) {
                return $parameter->getDefaultValue();
            }
            if (($reflectionType = $parameter->getType()) instanceof ReflectionNamedType) {
                if ($reflectionType->isBuiltin()) {
                    return $parameter->getDefaultValue();
                }
                return null;
            }
            foreach ($reflectionType->getTypes() as $type) {
                if ($type->isBuiltin()) {
                    return $parameter->getDefaultValue();
                }
            }
        } catch (ReflectionException) {
        }
        return null;
    }

    private function hasParameterSelfBuild(ReflectionParameter $parameter): bool
    {
        try {
            if ($parameter->hasType() === false) {
                return false;
            }
            if (($reflectionType = $parameter->getType()) instanceof ReflectionNamedType) {
                if ($reflectionType->isBuiltin()) {
                    return false;
                }
                return is_object($parameter->getDefaultValue());
            }
            foreach ($reflectionType->getTypes() as $type) {
                if ($type->isBuiltin() === false) {
                    return is_object($parameter->getDefaultValue());
                }
            }
        } catch (ReflectionException) {
        }
        return false;
    }
}
