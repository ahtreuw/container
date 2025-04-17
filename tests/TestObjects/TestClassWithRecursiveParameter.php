<?php

namespace Tests\Container\TestObjects;

class TestClassWithRecursiveParameter implements TestClassInterface
{

    public function __construct(TestClassWithRecursiveParameter $value)
    {
    }
}
