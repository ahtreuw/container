<?php declare(strict_types=1);

namespace Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    public const NO_ENTRY_WAS_FOUND_FOR_X_IDENTIFIER = 'No entry was found for %s identifier.';

    public function __construct(
        string         $id,
        int            $code = 0,
        Throwable|null $previous = null
    )
    {
        parent::__construct($id, $code, $previous, sprintf(self::NO_ENTRY_WAS_FOUND_FOR_X_IDENTIFIER, $id));
    }

}