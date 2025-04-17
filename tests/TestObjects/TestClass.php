<?php

namespace Tests\Container\TestObjects;

class TestClass implements TestClassInterface
{

    public function __construct(TestAliasClassInterface $alias)
    {
    }
}