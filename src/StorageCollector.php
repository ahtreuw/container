<?php declare(strict_types=1);

namespace Container;

use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class StorageCollector
{
    public const READ_INTERFACES = 1;
    public const READ_CONSTRUCTORS = 2;
    public const READ_METHODS = 4;
    public const READ_ALL = 7;

    #[Pure] public function __construct(
        protected array               $storage = [],
        protected ParametersInterface $parameters = new Parameters,
        protected FactoryInterface    $factory = new Factory
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function collect(
        string      $namespace,
        string      $directory,
        null|string $pattern = null,
        int         $flags = StorageCollector::READ_ALL
    ): void
    {
        $this->walk(rtrim($namespace, '\\'), $directory, $pattern, $flags);
    }

    public function getStorage(): array
    {
        return $this->storage;
    }

    /**
     * @throws Throwable
     */
    protected function walk(string $namespace, string $dirname, null|string $pattern, int $flags)
    {
        $dirname = rtrim($dirname, "/*") . "/*";
        $namespace = rtrim($namespace, "\\");

        foreach ($this->factory->call('glob', $dirname) as $path) {
            $info = pathinfo($path);

            if (is_null($info['extension'] ?? null)) {
                $this->walk($namespace . '\\' . $info['basename'], $path, $pattern, $flags);
                continue;
            }

            if (($info['extension'] ?? null) !== 'php') {
                continue;
            }

            $class = $namespace . '\\' . $info['filename'];

            if ($pattern && !preg_match($pattern, $class)) {
                continue;
            }

            $reflectionClass = $this->factory->createReflectionClass($class);
            $reflectionClass && $this->read($reflectionClass, $flags);
        }
    }

    public function read(ReflectionClass $reflectionClass, int $flags): void
    {
        if ($flags & StorageCollector::READ_INTERFACES) {
            $this->collectInterfaces($reflectionClass, ...$reflectionClass->getInterfaces());
        }

        if ($flags & StorageCollector::READ_CONSTRUCTORS || $flags & StorageCollector::READ_METHODS) {
            $reflectionMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            if ($flags & StorageCollector::READ_CONSTRUCTORS) {
                $this->collectConstructors($reflectionClass, ...$reflectionMethods);
            }

            if ($flags & StorageCollector::READ_METHODS) {
                $this->collectMethods($reflectionClass, ...$reflectionMethods);
            }
        }
    }

    protected function collectInterfaces(ReflectionClass $reflectionClass, ReflectionClass ...$interfaces)
    {
        foreach ($interfaces as $interface) {
            if (array_key_exists($interface->getName(), $this->storage)) {
                continue;
            }
            if ($interface->isUserDefined() === false) {
                continue;
            }
            $this->storage[$interface->getName()] = $reflectionClass->getName();
        }

    }

    protected function collectConstructors(ReflectionClass $reflectionClass, ReflectionMethod ...$reflectionMethods)
    {
        foreach ($reflectionMethods as $reflectionMethod) {
            $key = $reflectionClass->getName();

            if (
                $reflectionMethod->isConstructor() === false ||
                array_key_exists($key, $this->storage)
            ) {
                continue;
            }
            $this->storage[$key] = $this->parameters->with($key, $reflectionMethod);
        }
    }

    protected function collectMethods(ReflectionClass $reflectionClass, ReflectionMethod ...$reflectionMethods)
    {
        foreach ($reflectionMethods as $reflectionMethod) {
            $key = $reflectionClass->getName() . '::' . $reflectionMethod->getName();

            if ($reflectionMethod->isConstructor() ||
                $reflectionMethod->isDestructor() ||
                $reflectionMethod->isAbstract() ||
                $reflectionMethod->isStatic() ||
                str_starts_with($reflectionMethod->getName(), '_') ||
                array_key_exists($key, $this->storage)
            ) {
                continue;
            }
            $this->storage[$key] = $this->parameters->with($key, $reflectionMethod);
        }
    }
}
