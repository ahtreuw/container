<?php declare(strict_types=1);

namespace Container;

use Closure;
use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Processor implements ProcessorInterface
{
    #[Pure] public function __construct(protected ReflectorInterface $reflector = new Reflector)
    {
    }

    public function handle(ContainerInterface $container, string $id, mixed $value): bool
    {
        return $value instanceof Closure
            || $value instanceof ParametersInterface
            || is_object($value)
            || (is_string($value) && class_exists($value));
    }

    public function process(ContainerInterface $container, string $id, mixed $value, ...$arguments): mixed
    {
        if ($value instanceof Closure) {
            return $this->store($container, $id, $value($container, $id, ...$arguments));
        }
        if ($value instanceof ParametersInterface) {
            return $this->store($container, $id, $this->create($container, $id, $value, ...$arguments));
        }
        if (is_object($value)) {
            return $value;
        }
        return $this->process($container, $value, $container->getParameters($value), ...$arguments);
    }

    private function store(ContainerInterface $container, string $id, mixed $result)
    {
        $container->set($id, $result);
        return $result;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function create(
        ContainerInterface  $container,
        string              $id,
        ParametersInterface $parameters,
        mixed               ...$arguments
    ): mixed
    {
        [$class, $method] = array_pad(explode('::', $id, 2), 2, null);

        $params = $parameters->make($container, ...$arguments);

        if ($parameters->isConstructor()) {

            if (class_exists($class) === false) {
                throw new NotFoundException($id);
            }

            return $this->reflector->create($id, ...$params);
        }

        $object = $container->get($class);

        if (method_exists($object, $method) === false) {
            throw new NotFoundException($id);
        }

        return $this->reflector->call([$object, $method], ...$params);
    }
}
