<?php declare(strict_types=1);

namespace Vulpes\Container;

use DateInterval;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Vulpes\Container\Parameter\ArgParam;
use Vulpes\Container\Parameter\EnvParam;
use Vulpes\Container\Parameter\ObjParam;
use Vulpes\Container\Parameter\ValParam;

class StorageTest extends TestCase
{
    private Parser $parser;
    private StorageInterface $storage;

    protected function setUp(): void
    {
        $this->parser = $this->createMock(Parser::class);
        $this->storage = new Storage($this->parser);
    }

    /**
     * @dataProvider getArgProvider
     * @throws NotFoundException
     */
    public function testGetAndHasArg(string $id, mixed $value): void
    {
        self::assertFalse($this->storage->has($id));
        $this->storage->set($id, $value);
        self::assertTrue($this->storage->has($id));
        $result = $this->storage->get($id);
        self::assertSame($value, $result);
    }

    public function getArgProvider(): array
    {
        return [
            ['key', 1312],
            ['key', false],
            ['key', null],
            ['key', 'value'],
            ['key', ['array']],
            ['key', DateInterval::createFromDateString('1 day')],
        ];
    }

    /**
     * @dataProvider getArgProvider
     * @throws NotFoundException
     */
    public function testMergeArgs(string $id, mixed $value): void
    {
        self::assertFalse($this->storage->has($id));
        $this->storage->pushArgs([$id => $value]);
        self::assertTrue($this->storage->has($id));
        $result = $this->storage->get($id);
        self::assertSame($value, $result);
    }

    /**
     * @dataProvider getArgProvider
     * @throws NotFoundException
     */
    public function testMergeConf(string $id, mixed $value): void
    {
        self::assertFalse($this->storage->has($id));
        $this->storage->pushConf([$id => ['class' => 'className', 'params' => [['val' => $value]]]]);
        self::assertTrue($this->storage->has($id));
        $result = $this->storage->get($id);
        self::assertInstanceOf(Parameters::class, $result);
        self::assertInstanceOf(ValParam::class, $result->getParameter(0));
        self::assertSame($value, $result->getParameter(0)->getValue());
        self::assertSame($id, $result->getId());
        self::assertSame('className', $result->getClassName());

    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    public function testGetAndHasConf(string $id = 'key'): void
    {
        $conf = $this->createMock(Parameters::class);
        self::assertFalse($this->storage->has($id));
        $this->storage->set($id, $conf);
        self::assertTrue($this->storage->has($id));
        $result = $this->storage->get($id);
        self::assertSame($conf, $result);
    }

    /**
     * @throws NotFoundException
     */
    public function testNotFoundException(): void
    {
        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionCode(NotFoundException::STORAGE_GET);
        $this->storage->get('not-existing-key');
    }

    /**
     * @dataProvider createParameterProvider
     * @throws NotFoundException
     */
    public function testCreateParameter(string $type, string $expected, string $value, null|string $expectedValue): void
    {
        $this->storage->pushConf(['id' => [[$type => $value]]]);

        $result = $this->storage->get('id');

        self::assertInstanceOf(Parameters::class, $result);
        self::assertInstanceOf($expected, $result->getParameter(0));
        self::assertSame($expectedValue, $result->getParameter(0)->getValue());
    }

    public function createParameterProvider(): array
    {
        return [
            ['val', ValParam::class, 'val-01', 'val-01'],
            ['value', ValParam::class, 'val-02', 'val-02'],
            ['env', EnvParam::class, 'val-03', 'val-03'],
            ['environment', EnvParam::class, 'val-04', 'val-04'],
            ['arg', ArgParam::class, 'val-05', 'val-05'],
            ['argument', ArgParam::class, 'val-06', 'val-06'],
            ['obj', ObjParam::class, 'val-07', 'val-07'],
            ['object', ObjParam::class, 'val-08', 'val-08'],
            ['--unknown--', ValParam::class, 'val-09', null],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testReadConfig(): void
    {
        $this->parser->expects($this->once())->method('parse')->with('yaml')->willReturn([
            'conf' => ['Example' => null],
            'args' => ['ARG_KEY' => null],
        ]);

        $this->storage->readConfig('yaml');

        self::assertTrue($this->storage->has('Example'));
        self::assertTrue($this->storage->has('ARG_KEY'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testReadConfigFile(): void
    {
        $this->parser->expects($this->once())->method('parseFile')->with('filename.yaml')->willReturn([
            'conf' => ['Example' => null],
            'args' => ['ARG_KEY' => null],
        ]);

        $this->storage->readConfigFile('filename.yaml');

        self::assertTrue($this->storage->has('Example'));
        self::assertTrue($this->storage->has('ARG_KEY'));
    }

    public function testReadConfigExceptionHandling(): void
    {
        $this->parser->expects($this->once())->method('parse')->willReturnCallback(function () {
            throw new \Exception;
        });
        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionCode(ContainerException::PARSEYAML);
        $this->storage->readConfig('yaml');
    }

    public function testReadConfigFileExceptionHandling(): void
    {
        $this->parser->expects($this->once())->method('parseFile')->willReturnCallback(function () {
            throw new \Exception;
        });
        self::expectException(ContainerExceptionInterface::class);
        self::expectExceptionCode(ContainerException::PARSEYAML);
        $this->storage->readConfigFile('filename.yaml');
    }
}
