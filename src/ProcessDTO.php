<?php declare(strict_types=1);

namespace Container;

class ProcessDTO
{
    public function __construct(
        public readonly ContainerInterface $container,
        public readonly string $id,
        public readonly mixed $value,
        public readonly array $arguments
    )
    {
    }
}
