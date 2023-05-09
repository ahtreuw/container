<?php declare(strict_types=1);

namespace Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class Container implements ContainerInterface
{
    /** @var ProcessorInterface[] */
    private array $processors = [];

    public function __construct(
        protected array               $storage = [],
        protected ReflectorInterface  $reflector = new Reflector,
        protected ParametersInterface $parameters = new Parameters,
        protected ProcessorInterface  $processor = new Processor
    )
    {
        $this->pushProcessor($this->processor);
    }

    public function get(string $id, mixed ...$arguments): mixed
    {
        try {
            if (array_key_exists($id, $this->storage)) {
                return $this->process($id, $this->storage[$id], ...$arguments);
            }

            if (str_ends_with($id, 'Interface')) {
                return $this->get(substr($id, 0, -9), ...$arguments);
            }

            return $this->process($id, $this->getParameters($id), ...$arguments);

        } catch (Throwable $exception) {
            if ($exception instanceof ContainerException && $exception->getId() === $id) {
                throw $exception;
            }
            throw new ContainerException($id, sprintf('Error while retrieving the entry %s.', $id), 0, $exception);
        }
    }

    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->storage) || class_exists($id)) {
            return true;
        }

        if (str_ends_with($id, 'Interface') && class_exists(substr($id, 0, -9))) {
            return true;
        }

        [$class, $method] = array_pad(explode('::', $id, 2), 2, null);

        return class_exists($class) && (!$method || method_exists($class, $method));
    }

    public function set(string $id, mixed $entity): void
    {
        $this->storage[$id] = $entity;
    }

    public function pushProcessor(ProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }

    public function call(object $object, string $methodName, mixed ...$arguments): mixed
    {
        $id = get_class($object) . '::' . $methodName;

        if (method_exists($object, $methodName) === false) {
            throw new NotFoundException($id);
        }

        $parameters = $this->getParameters($id);
        $parameters = $parameters->make($this, ...$arguments);

        return $this->reflector->call([$object, $methodName], ...$parameters);
    }

    public function getParameters(string $id): ParametersInterface
    {
        if (array_key_exists($id, $this->storage) &&
            $this->storage[$id] instanceof ParametersInterface) {
            return $this->storage[$id];
        }

        $parameters = $this->parameters->with($id, $this->reflector->createReflectionMethod($id));

        if (array_key_exists($id, $this->storage) === false) {
            $this->storage[$id] = $parameters;
        }

        return $parameters;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Throwable
     */
    public function process(string $id, mixed $value, mixed  ...$arguments): mixed
    {
        foreach ($this->processors as $processor) {
            if ($processor->handle($this, $id, $value)) {
                return $processor->process($this, $id, $value, ...$arguments);
            }
        }
        return $value;
    }
}
