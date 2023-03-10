<?php declare(strict_types=1);

namespace Vulpes\Container;

use Closure;

interface FactoryInterface
{
    public function createInstance(string $className, mixed ...$parameters): object;

    public function invokeClosure(Closure $closure, mixed ...$parameters): mixed;

    public function createParameters(string $id, string $class): Parameters;
}
