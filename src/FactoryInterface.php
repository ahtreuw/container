<?php declare(strict_types=1);

namespace Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

interface FactoryInterface
{
    /**
     * @throws Throwable
     */
    public function createObject(string $className, mixed ...$parameters): object;

    /**
     * @throws NotFoundExceptionInterface
     */
    public function createReflectionMethod(object|string $class, string $method = null): ?ReflectionMethod;

    public function getParameterReflectionNamedType(
        ReflectionParameter $parameter,
        ContainerInterface  $container = null
    ): ?ReflectionNamedType;


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createOptionalParameterValue(
        ReflectionParameter      $parameter,
        null|ContainerInterface       $container,
        null|ReflectionNamedType $type
    ): mixed;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createRequiredParameterValue(
        ReflectionParameter      $parameter,
        null|ContainerInterface       $container,
        null|ReflectionNamedType $type,
        ReflectionMethod         $method,
        string                   $className
    ): mixed;
}
