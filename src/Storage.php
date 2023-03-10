<?php declare(strict_types=1);

namespace Vulpes\Container;

use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerExceptionInterface;
use Throwable;
use Vulpes\Container\Parameter\ArgParam;
use Vulpes\Container\Parameter\EnvParam;
use Vulpes\Container\Parameter\ObjParam;
use Vulpes\Container\Parameter\ValParam;

class Storage implements StorageInterface
{
    private array $args = [];
    private array $conf = [];

    public function __construct(private Parser $parser) {}

    /**
     * @throws ContainerExceptionInterface
     */
    public function readConfig(string $yaml): void
    {
        try {
            ['conf' => $conf, 'args' => $args] = $this->parser->parse($yaml);
        } catch (Throwable $exception) {
            throw new ContainerException('Error while parse Yaml config.', ContainerException::PARSEYAML, $exception);
        }

        $this->pushArgs($args);
        $this->pushConf($conf);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function readConfigFile(string $filename): void
    {
        try {
            ['conf' => $conf, 'args' => $args] = $this->parser->parseFile($filename);
        } catch (Throwable $exception) {
            throw new ContainerException(sprintf('Error while read Yaml config file %s.', $filename), ContainerException::PARSEYAML, $exception);
        }

        $this->pushArgs($args);
        $this->pushConf($conf);
    }

    public function pushArgs(array $args): void
    {
        $this->args = array_merge($this->args, $args);
    }

    public function pushConf(array $conf): void
    {
        $this->conf = array_merge($this->conf, $conf);
    }

    /**
     * @throws NotFoundException
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->args) && is_object($this->args[$id])) {
            return $this->args[$id];
        }

        if (array_key_exists($id, $this->conf)) {

            if ($this->conf[$id] instanceof Parameters) {
                return $this->conf[$id];
            }

            if (is_array($this->conf[$id])) {
                return $this->createParameters($id, $this->conf[$id]);
            }
        }

        if (array_key_exists($id, $this->args)) {
            return $this->args[$id];
        }

        throw new NotFoundException($id, NotFoundException::STORAGE_GET);
    }

    public function set(string $id, mixed $value)
    {
        if ($value instanceof Parameters) {
            $this->conf[$id] = $value;
            return;
        }
        $this->args[$id] = $value;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->args) || array_key_exists($id, $this->conf);
    }

    #[Pure] private function createParameters(string $id, array $conf): Parameters
    {
        $classname = $conf['class'] ?? $conf['className'] ?? $conf['classname'] ?? $id;
        $parameters = is_numeric(key($conf)) ? $conf : ($conf['params'] ?? $conf['parameters'] ?? []);
        return new Parameters($id, $classname, ...$this->prepareParamsList($parameters));
    }

    /**
     * @return Parameter[]
     */
    #[Pure] private function prepareParamsList(array $params): array
    {
        $parameters = [];
        foreach ($params as $item) {
            $parameters[] = $this->createParameter(key($item), current($item));
        }
        return $parameters;
    }

    #[Pure] private function createParameter(string $key, mixed $value): Parameter
    {
        if ($key === 'arg' || $key === 'argument') {
            return new ArgParam($value);
        }
        if ($key === 'env' || $key === 'environment') {
            return new EnvParam($value);
        }
        if ($key === 'obj' || $key === 'object') {
            return new ObjParam($value);
        }
        if ($key === 'val' || $key === 'value') {
            return new ValParam($value);
        }
        return new ValParam(null);
    }
}
