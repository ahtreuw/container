<?php declare(strict_types=1);

namespace Container;

use JetBrains\PhpStorm\Pure;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    #[Pure] public function __construct(
        string    $id,
        string    $message = "",
        int       $code = 0,
        Throwable $previous = null
    )
    {
        $message = $message ?: sprintf('No entry was found for %s identifier.', $id);
        parent::__construct($id, $message, $code, $previous);
    }
}
