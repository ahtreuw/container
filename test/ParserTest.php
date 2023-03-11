<?php declare(strict_types=1);

namespace Vulpes\Container;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;
use Vulpes\Container\Parser\SymfonyParser;

class ParserTest extends TestCase
{
    public function testParseYaml(): void
    {
        $symfonyParser = $this->createMock(Parser::class);

        $parser = new SymfonyParser($symfonyParser);

        $symfonyParser->expects($this->once())->method('parseFile')
            ->with('filename', SymfonyParser::FLAGS);

        $parser->parseFile('filename');
    }

}
