<?php declare(strict_types=1);

namespace Tests\TestObjects;

class TestClassWithBuiltInNotOptionalNotAllowsNullParameter
{
    public function __construct(int $value)
    {
    }
}