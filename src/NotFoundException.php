<?php declare(strict_types=1);

namespace Vulpes\Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException
 *
 * @example ```
 * throw new NotFoundException(sprintf('No entry was found for %s identifier.', $id), NotFoundException::GET);
 * ```
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    public const GET = 201;
    public const CREATE_PARAMETERS = 202;
    public const STORAGE_GET = 204;
}
