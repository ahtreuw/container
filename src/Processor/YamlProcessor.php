<?php declare(strict_types=1);

namespace Container\Processor;

use Container\Factory;
use Container\FactoryInterface;
use Container\ProcessHandlerInterface;
use Container\ProcessorInterface;
use Container\ProcessDTO;
use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class YamlProcessor implements ProcessorInterface
{
    #[Pure] public function __construct(
        protected FactoryInterface $factory = new Factory
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function process(ProcessDTO $dto, ProcessHandlerInterface $handler): mixed
    {
        if (!is_array($dto->value) || !array_key_exists('params', $dto->value) ||
            !is_array($dto->value['params']) || !class_exists($dto->value['class'] ?? $dto->id)) {
            return $handler->handle($dto);
        }

        $parameters = [];
        $arguments = $dto->arguments;
        foreach ($dto->value['params'] as $index => $parameter) {
            (is_numeric($index) === false) && $parameter = [$index => $parameter];

            [$type, $name] = array_pad(explode(':', key($parameter), 2), 2, count($parameters));

            if (is_string($name) && array_key_exists($name, $arguments)) {
                $parameters[$name] = $arguments[$name];
                unset($arguments[$name]);
                continue;
            }

            $parameters[$name] = $this->getValueByType($dto->container, $type, current($parameter), $arguments);
        }

        $object = $this->factory->createObject($dto->value['class'] ?? $dto->id, ...$parameters);

        $dto->container->set($dto->id, $object);

        return $object;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    private function getValueByType(ContainerInterface $container, string $type, mixed $value, array &$args): mixed
    {
        if ($type === 'env') {
            return $this->factory->call('getenv', $value);
        }
        if ($type === 'val') {
            return $value;
        }
        if ($type === 'obj') {
            return $container->get($value);
        }
        if ($type === 'arg' && $container->has($value)) {
            return $container->get($value);
        }
        return array_shift($args);
    }
}