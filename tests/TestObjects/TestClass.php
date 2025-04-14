<?php

namespace Tests\TestObjects;

class TestClass implements TestClassInterface
{

    public function __construct(TestAliasClassInterface $alias)
    {
    }
}