<?php declare(strict_types=1);

namespace Container;

use Exception;
use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerExceptionInterface;
use Throwable;

class ContainerException extends Exception implements ContainerExceptionInterface
{
    #[Pure] public function __construct(
        private string $id,
        string $message = "",
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getId(): string
    {
        return $this->id;
    }
}