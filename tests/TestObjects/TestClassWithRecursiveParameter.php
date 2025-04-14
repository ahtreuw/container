<?php

namespace Tests\TestObjects;

class TestClassWithRecursiveParameter implements TestClassInterface
{

    public function __construct(TestClassWithRecursiveParameter $value)
    {
    }
}
