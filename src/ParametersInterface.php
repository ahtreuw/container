<?php declare(strict_types=1);

namespace Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionMethod;

interface ParametersInterface
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function make(ContainerInterface $container, mixed ...$arguments): array;

    public function with(string $id, null|ReflectionMethod $reflectionMethod): ParametersInterface;

    public function isConstructor(): bool;
}