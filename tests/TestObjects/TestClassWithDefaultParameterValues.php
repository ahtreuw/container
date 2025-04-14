<?php

namespace Tests\TestObjects;

use stdClass;

class TestClassWithDefaultParameterValues
{
    public function __construct(
        public $name,
        public $object = new stdClass
    )
    {
    }
}