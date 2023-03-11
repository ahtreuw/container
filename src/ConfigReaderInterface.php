<?php declare(strict_types=1);

namespace Vulpes\Container;

use Psr\Container\ContainerExceptionInterface;

interface ConfigReaderInterface
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function readConfig(string $yaml): void;

    /**
     * @throws ContainerExceptionInterface
     */
    public function readConfigFile(string $filename): void;
}