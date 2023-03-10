<?php declare(strict_types=1);

namespace Vulpes\Container\Parameter;

use Vulpes\Container\Parameter;

abstract class AbstractParam implements Parameter
{
    public function __construct(private mixed $value) {}

    public function getValue(): mixed
    {
        return $this->value;
    }
}
