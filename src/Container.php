<?php declare(strict_types=1);

namespace Container;

use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class Container implements ContainerInterface
{
    /** @var ProcessorInterface[] */
    private array $processors = [];

    #[Pure] public function __construct(
        protected array                   $storage = [],
        protected FactoryInterface        $factory = new Factory,
        protected ParametersInterface     $parameters = new Parameters,
        protected ProcessHandlerInterface $handler = new ProcessHandler
    ) {}

    public function get(string $id, mixed ...$arguments): mixed
    {
        try {
            if (array_key_exists($id, $this->storage)) {
                return $this->handle($id, $this->storage[$id], $arguments);
            }

            if (str_ends_with($id, 'Interface')) {
                return $this->get(substr($id, 0, -9), ...$arguments);
            }

            return $this->handle($id, $id, $arguments);

        } catch (Throwable $exception) {
            if ($exception instanceof ContainerException && $exception->getId() === $id) {
                throw $exception;
            }
            $message = 'Error while retrieving the entry %s.';
            throw new ContainerException($id, sprintf($message, $id), 0, $exception);
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

    public function add(ProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }

    public function call(object $object, string $methodName, mixed ...$arguments): mixed
    {
        $id = get_class($object) . '::' . $methodName;

        if (is_callable([$object, $methodName]) === false) {
            throw new NotFoundException($id);
        }

        return $this->factory->call([$object, $methodName], ...$this->getParameters($id)->make($this, ...$arguments));
    }

    public function getParameters(string $id): ParametersInterface
    {
        if (array_key_exists($id, $this->storage) && $this->storage[$id] instanceof ParametersInterface) {
            return $this->storage[$id];
        }
        return $this->parameters->with($id, $this->factory->createReflectionMethod($id));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    private function handle(string $id, mixed $value, array $arguments): mixed
    {
        return $this->handler->with(...$this->processors)
            ->handle($this->factory->createObject(ProcessDTO::class, $this, $id, $value, $arguments));
    }
}
