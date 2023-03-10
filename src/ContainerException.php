<?php declare(strict_types=1);

namespace Vulpes\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * ContainerException
 *
 * @example ```
 * throw new ContainerException(sprintf('Error while retrieving the entry %s.', $id), ContainerException::GET);
 * ```
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
    public const GET = 101;
    public const INVOKE = 102;
    public const BUILTIN = 104;
    public const PARSEYAML = 108;
}
