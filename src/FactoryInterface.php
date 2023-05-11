<?php declare(strict_types=1);

namespace Container;

use ReflectionClass;
use ReflectionMethod;
use Throwable;

interface FactoryInterface
{
    /**
     * @throws Throwable
     */
    public function createObject(string $class, mixed ...$parameters): object;

    /**
     * @throws Throwable
     */
    public function call(callable $callable, mixed ...$parameters): mixed;

    public function createReflectionClass(object|string $objectOrClass): null|ReflectionClass;

    public function createReflectionMethod(object|string $objectOrMethod, string|null $method = null): null|ReflectionMethod;
}