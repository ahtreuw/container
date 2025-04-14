<?php declare(strict_types=1);

namespace Tests;

use Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestObjects\TestClassWithBuiltInNotOptionalNotAllowsNullParameter;
use Tests\TestObjects\TestClassWithNotImplementableParameter;

class ContainerNotFoundExceptionsTest extends TestCase
{

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testGetClass(): void
    {
        $container = new Container;

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', 'notExistingEntity'));

        $container->get('notExistingEntity');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testGetInterface(): void
    {
        $container = new Container;

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', 'notExistingEntityInterface'));

        $container->get('notExistingEntityInterface');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testWithBuiltInNotOptionalNotAllowsNullParameter(): void
    {
        $container = new Container;
        $className = TestClassWithBuiltInNotOptionalNotAllowsNullParameter::class;

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', "$className::\$value"));

        $container->get($className);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testWithNotImplementableParameter(): void
    {
        $container = new Container;
        $className = TestClassWithNotImplementableParameter::class;

        self::expectException(NotFoundExceptionInterface::class);
        self::expectExceptionMessage(sprintf('No entry was found for %s identifier.', "$className::\$value"));

        $container->get($className);
    }
}
