<?php

namespace Tests\TestObjects;

class TestAliasClass implements TestAliasClassInterface
{

    public function __construct(TestClassInterface $alias)
    {
    }
}