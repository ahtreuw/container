<?php declare(strict_types=1);

namespace Vulpes\Container;

interface Parser
{
    public function parse(string $value): mixed;

    public function parseFile(string $filename): mixed;
}