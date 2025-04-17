<?php declare(strict_types=1);

namespace Tests\Container\TestObjects;

use ArrayAccess;

class TestClassWithNotImplementableParameter
{
    public function __construct(ArrayAccess $value)
    {
    }
}