<?php declare(strict_types=1);

namespace Vulpes\Container\Parser;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Vulpes\Container\ParserInterface as ParserInterface;

class SymfonyParser implements ParserInterface
{
    public const FLAGS = Yaml::PARSE_CONSTANT | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE;

    public function __construct(private Parser $parser) {}

    public function parseFile(string $filename): mixed
    {
        return $this->parser->parseFile($filename, SymfonyParser::FLAGS);
    }
}