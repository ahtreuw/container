<?php declare(strict_types=1);

namespace Vulpes\Container;

interface StorageInterface
{
    public function get(string $id): mixed;

    public function set(string $id, mixed $value);

    public function has(string $id): bool;
}
