<?php declare(strict_types=1);

namespace Container;

use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ProcessHandler implements ProcessHandlerInterface
{
    /** @var ProcessorInterface[] */
    protected array $processors = [];

    #[Pure] public function __construct(
        protected FactoryInterface $factory = new Factory
    ){}

    public function with(ProcessorInterface ...$processors): static
    {
        $new = clone $this;
        $new->processors = $processors;
        return $new;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function prepareDTO(ProcessDTO $dto, string $id): ProcessDTO
    {
        return new ProcessDTO($dto->container, $id, $dto->container->getParameters($id), $dto->arguments);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function handle(ProcessDTO $dto): mixed
    {
        if ($processor = array_shift($this->processors)) {
            return $processor->process($dto, $this);
        }

        if ($dto->value instanceof ParametersInterface) {
            return $this->process($dto, $dto->value);
        }

        if (is_object($dto->value)) {
            return $dto->value;
        }

        if (is_string($dto->value) && (class_exists($dto->value) || str_contains($dto->value, '::'))) {
            return $this->handle($this->prepareDTO($dto, $dto->value));
        }

        if (is_array($dto->value) && count($dto->value) === 2 && isset($dto->value[0]) && isset($dto->value[1])) {
            if (is_string($dto->value[0]) && class_exists($dto->value[0]) && is_string($dto->value[1])) {
                return $this->handle($this->prepareDTO($dto, $dto->value[0] . '::' . $dto->value[1]));
            }
            if (is_object($dto->value[0]) && is_string($dto->value[1])) {
                return $this->call($dto, $dto->container->getParameters($dto->id), $dto->value[0], $dto->value[1]);
            }
        }

        if (is_scalar($dto->value)) {
            throw new NotFoundException($dto->id);
        }

        return $dto->value;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function process(ProcessDTO $dto, ParametersInterface $parameters)
    {
        [$class, $method] = array_pad(explode('::', $dto->id, 2), 2, null);

        if ($parameters->isConstructor()) {
            return $this->createObject($dto, $parameters, $class);
        }

        return $this->call($dto, $parameters, $class, $method);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function createObject(ProcessDTO $dto, ParametersInterface $parameters, string $class): object
    {
        if (class_exists($class) === false) {
            throw new NotFoundException($dto->id);
        }

        $params = $parameters->make($dto->container, ...$dto->arguments);

        $result = $this->factory->createObject($class, ...$params);

        $dto->container->set($dto->id, $result);

        return $result;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function call(
        ProcessDTO          $dto,
        ParametersInterface $parameters,
        object|string       $class,
        string              $method
    ): mixed
    {
        $object = is_object($class) ? $class : $dto->container->get($class);

        if (is_callable([$object, $method]) === false) {
            throw new NotFoundException($dto->id);
        }

        $params = $parameters->make($dto->container, ...$dto->arguments);

        return $this->factory->call([$object, $method], ...$params);
    }
}