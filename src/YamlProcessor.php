<?php declare(strict_types=1);

namespace Container;

use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class YamlProcessor implements ProcessorInterface
{
    #[Pure] public function __construct(protected ReflectorInterface $reflector = new Reflector)
    {
    }

    public function handle(ContainerInterface $container, string $id, mixed $value): bool
    {
        return is_array($value)
            && array_key_exists('params', $value) && is_array($value['params'])
            && class_exists($value['class'] ?? $id);
    }

    public function process(ContainerInterface $container, string $id, mixed $value, ...$arguments): object
    {
        $parameters = [];
        foreach ($value['params'] as $index => $parameter) {
            (is_numeric($index) === false) && $parameter = [$index => $parameter];

            [$type, $name] = array_pad(explode(':', key($parameter), 2), 2, count($parameters));

            if (is_string($name) && array_key_exists($name, $arguments)) {
                $parameters[$name] = $arguments[$name];
                unset($arguments[$name]);
                continue;
            }

            $parameters[$name] = $this->getValueByType($container, $type, current($parameter), $arguments);
        }

        $object = $this->reflector->create($value['class'] ?? $id, ...$parameters);

        $container->set($id, $object);

        return $object;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getValueByType(ContainerInterface $container, string $type, mixed $value, array &$args): mixed
    {
        if ($type === 'env') {
            return $this->reflector->call('getenv', $value);
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