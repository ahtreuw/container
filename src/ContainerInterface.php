<?php declare(strict_types=1);

namespace Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * @throws NotFoundExceptionInterface|ContainerExceptionInterface
     */
    public function get(string $id, mixed ...$arguments): mixed;

    public function set(string $id, mixed $entity): void;

    /**
     * @throws NotFoundExceptionInterface|ContainerExceptionInterface|Throwable
     */
    public function call(object $object, string $methodName, mixed ...$arguments): mixed;

    /**
     * @throws NotFoundExceptionInterface|ContainerExceptionInterface
     */
    public function getParameters(string $id): ParametersInterface;
}