<?php declare(strict_types=1);

namespace Vulpes\Container;

class Parameters
{
    private string $id;
    private string $className;
    private array $parameters;

    public function __construct(string $id, string $className, Parameter ...$parameters)
    {
        $this->id = $id;
        $this->className = $className;
        $this->parameters = $parameters;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(int $offset): Parameter
    {
        return $this->parameters[$offset];
    }
}
