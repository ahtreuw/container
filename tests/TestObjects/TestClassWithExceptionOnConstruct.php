<?php

namespace Tests\TestObjects;

use Throwable;

class TestClassWithExceptionOnConstruct implements TestClassInterface
{

    public function __construct(Throwable $exception)
    {
        throw $exception;
    }
}