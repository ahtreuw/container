<?php declare(strict_types=1);

namespace Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

interface ProcessorInterface
{
    public function handle(ContainerInterface $container, string $id, mixed $value): bool;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(ContainerInterface $container, string $id, mixed $value, mixed ...$arguments): mixed;
}