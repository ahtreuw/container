<?php

namespace Tests\TestObjects;

use ArrayAccess;
use Psr\Container\ContainerInterface;
use Stringable;
use Throwable;

class TestClassWithOptionalParameter
{
    public null|ContainerInterface $container;

    public function __construct(
        public null|ArrayAccess|(Stringable&Throwable) $arrayAccess,
        public null|ArrayAccess                        $onlyArrayAccess,
        ContainerInterface                             $container = null
    )
    {
        $this->container = $container;
    }
}
