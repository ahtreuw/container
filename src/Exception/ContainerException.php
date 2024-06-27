<?php declare(strict_types=1);

namespace Container\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Throwable;

class ContainerException extends Exception implements ContainerExceptionInterface
{
    public const TRANSFORM_EXCEPTION = 1;
    public const GET_REQUIRED_VALUE = 3;
    public const NOT_FOUND = 4;
    public const ERROR_WHILE_RETRIEVING_THE_ENTRY = 'Error while retrieving the entry %s.';
    public const ERROR_WHILE_RETRIEVING_INVALID_STORAGE_VALUE_TYPE = 'Error while retrieving the entry %s, inValid storage value type: %s';
    public const ERROR_WHILE_RETRIEVING_MISSING_PARAMETER = 'Error while retrieving the entry %s, %s parameter missing: $%s.';

    public function __construct(
        private readonly string $id,
        int                     $code = 0,
        Throwable|null          $previous = null,
        null|string             $message = null
    )
    {
        if (is_null($message)) {
            $message = sprintf(self::ERROR_WHILE_RETRIEVING_THE_ENTRY, $id);
        }
        parent::__construct($message, $code, $previous);
    }

    public function getId(): string
    {
        return $this->id;
    }
}
