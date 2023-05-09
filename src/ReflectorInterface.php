<?php declare(strict_types=1);

namespace Container;

use ReflectionClass;
use ReflectionMethod;

interface ReflectorInterface
{
    public function create(string $class, mixed ...$parameters): object;

    public function call(callable $callable, mixed ...$parameters): mixed;

    public function createReflectionClass(object|string $objectOrClass): null|ReflectionClass;

    public function createReflectionMethod(object|string $objectOrMethod, string|null $method = null): null|ReflectionMethod;
}