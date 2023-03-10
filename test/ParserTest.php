<?php declare(strict_types=1);

namespace Vulpes\Container;

use PHPUnit\Framework\TestCase;
use Vulpes\Container\Parser\SymfonyParser;

class ParserTest extends TestCase
{
    public function testParseYaml(): void
    {
        $symfonyParser = $this->createMock(\Symfony\Component\Yaml\Parser::class);

        $parser = new SymfonyParser($symfonyParser);

        $symfonyParser->expects($this->once())->method('parseFile')->with('filename', SymfonyParser::FLAGS);
        $symfonyParser->expects($this->once())->method('parse')->with('yaml', SymfonyParser::FLAGS);

        $parser->parseFile('filename');
        $parser->parse('yaml');
    }

}
