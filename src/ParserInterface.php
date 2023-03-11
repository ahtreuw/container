<?php declare(strict_types=1);

namespace Vulpes\Container;

interface ParserInterface
{
    public function parseFile(string $filename): mixed;
}