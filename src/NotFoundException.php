<?php declare(strict_types=1);

namespace Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    public const NOT_FOUND_MESSAGE = 'No entry was found for %s identifier.';
    public const CLASS_NOT_FOUND = 3;
    public const  INTERFACE_NOT_FOUND = 5;
    public const  BUILTIN_NOT_FOUND = 8;
    public const  DEFAULT_NOT_FOUND = 9;

    public function __construct(string $id, int $code)
    {
        parent::__construct(sprintf(self::NOT_FOUND_MESSAGE, $id), $code);
    }
}
