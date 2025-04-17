<?php

namespace Tests\Container\TestObjects;

class TestAliasClass implements TestAliasClassInterface
{

    public function __construct(TestClassInterface $alias)
    {
    }
}