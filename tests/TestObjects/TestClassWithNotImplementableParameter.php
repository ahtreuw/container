<?php declare(strict_types=1);

namespace Tests\TestObjects;

use ArrayAccess;

class TestClassWithNotImplementableParameter
{
    public function __construct(ArrayAccess $value)
    {
    }
}