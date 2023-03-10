<?php declare(strict_types=1);

namespace Vulpes\Container;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class Container implements ContainerInterface
{
    public function __construct(
        private FactoryInterface    $factory,
        private StorageInterface    $storage,
        private null|CacheInterface $cache = null
    ) {}

    public function get(string $id): mixed
    {
        try {

            if ($this->storage->has($id)) {
                return $this->getStorageValue($id);
            }

            if (interface_exists($id)) {
                return $this->get(substr($id, 0, -9));
            }

            if (class_exists($id) === false) {
                throw new NotFoundException(sprintf('No entry was found for %s identifier.', $id), NotFoundException::GET);
            }

            $parameters = $this->createParameters($id, $id);
            $object = $this->createInstance($parameters);

            $this->storage->set($id, $object);

            return $object;

        } catch (ContainerExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ContainerException(sprintf('Error while retrieving the entry %s.', $id), ContainerException::GET, $exception);
        }
    }

    public function set(string $id, mixed $value): void
    {
        $this->storage->set($id, $value);
    }

    public function has(string $id): bool
    {
        if ($this->storage->has($id) || class_exists($id)) {
            return true;
        }

        if (interface_exists($id)) {
            return $this->has(substr($id, 0, -9));
        }

        return false;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getStorageValue(string $id): mixed
    {
        $value = $this->storage->get($id);

        if (is_object($value)) {
            return $this->prepareStorageObjectValue($id, $value);
        }

        if (is_string($value)) {
            return $this->prepareStorageStringValue($id, $value);
        }

        return $value;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createInstance(Parameters $parameters): object
    {
        $constructorParameters = [];
        foreach ($parameters->getParameters() as $parameter) {
            $constructorParameters[] = $this->getParameterValue($parameter);
        }
        return $this->factory->createInstance($parameters->getClassName(), ...$constructorParameters);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function prepareStorageObjectValue(string $id, mixed $value): object
    {
        if ($value instanceof Parameters) {
            $value = $this->createInstance($value);
            $this->storage->set($id, $value);
            return $value;
        }

        if (get_class($value) === Closure::class) {
            try {
                $value = $this->factory->invokeClosure($value, $this, $id);
            } catch (Throwable $exception) {
                throw new ContainerException(sprintf('Error while invoke the closure %s.', $id), ContainerException::INVOKE, $exception);
            }
            $this->storage->set($id, $value);
            return $value;
        }

        return $value;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function prepareStorageStringValue(string $id, string $value): mixed
    {
        if ($this->storage->has($value)) {
            $value = $this->get($value);
            $this->storage->set($id, $value);
            return $value;
        }

        if (class_exists($value)) {
            $parameters = $this->factory->createParameters($id, $value);
            $value = $this->createInstance($parameters);
            $this->storage->set($id, $value);
            return $value;
        }

        return $value;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getParameterValue(Parameter $parameter): mixed
    {
        if ($parameter instanceof Parameter\EnvParam) {
            return getenv($parameter->getValue());
        }
        if ($parameter instanceof Parameter\ArgParam) {
            return $this->storage->get($parameter->getValue());
        }
        if ($parameter instanceof Parameter\ObjParam) {
            return $this->get($parameter->getValue());
        }
        if ($parameter instanceof Parameter\ValParam) {
            return $parameter->getValue();
        }
        return null; // not expected to be here
    }

    /**
     * @throws InvalidArgumentException
     */
    private function createParameters(string $id, string $class): Parameters
    {
        if ($this->cache?->has('params:' . $id)) {
            return $this->cache->get('params:' . $id);
        }

        $parameters = $this->factory->createParameters($id, $class);

        $this->cache?->set('params:' . $id, $parameters);

        return $parameters;
    }
}
