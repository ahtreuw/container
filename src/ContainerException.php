<?php declare(strict_types=1);

namespace Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Throwable;

class ContainerException extends Exception implements ContainerExceptionInterface
{
    public const CONTAINER_ERROR = 'Error while retrieving the entry for %s identifier.';
    public const CIRCULAR_DEPENDENCY = 33;
    public const PARAMETER_DEPENDENCY = 35;
    public const CLOSURE_EXCEPTION = 37;
    public const CREATE_EXCEPTION = 39;
    public const CIRCULAR_DEPENDENCY_MESSAGE = '(circular dependency detected)';
    public const PARAMETER_DEPENDENCY_MESSAGE = '(circular dependency via parameter)';
    public const CLOSURE_EXCEPTION_MESSAGE = '(while call the closure)';
    public const CREATE_EXCEPTION_MESSAGE = '(while create the object)';

    public function __construct(string $id, int $code, ?Throwable $previous = null)
    {
        $message = sprintf(self::CONTAINER_ERROR . ' %s', $id, $this->suffix($code));
        parent::__construct(trim($message), $code, $previous);
    }

    private function suffix(int $code): string
    {
        return match ($code) {
            self::CIRCULAR_DEPENDENCY => self::CIRCULAR_DEPENDENCY_MESSAGE,
            self::PARAMETER_DEPENDENCY => self::PARAMETER_DEPENDENCY_MESSAGE,
            self::CLOSURE_EXCEPTION => self::CLOSURE_EXCEPTION_MESSAGE,
            self::CREATE_EXCEPTION => self::CREATE_EXCEPTION_MESSAGE,
            default => '',
        };
    }
}
