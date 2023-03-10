<?php declare(strict_types=1);

namespace Vulpes\Container\Parser;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Vulpes\Container\Parser as ParserInterface;

class SymfonyParser implements ParserInterface
{
    public const FLAGS = Yaml::PARSE_CONSTANT | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE;

    public function __construct(private Parser $parser) {}

    public function parse(string $value): mixed
    {
        return $this->parser->parse($value, SymfonyParser::FLAGS);
    }

    public function parseFile(string $filename): mixed
    {
        return $this->parser->parseFile($filename, SymfonyParser::FLAGS);
    }
}