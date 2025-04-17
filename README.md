# A dependency injection (DI) container

This repository contains the [PHP FIG PSR-11] Container implementation.

[![codecov](https://codecov.io/gh/ahtreuw/container/branch/main/graph/badge.svg)](https://codecov.io/gh/ahtreuw/container)

## Install

Via Composer Package is available on [Packagist], You can install it using [Composer].

``` bash
$ composer require vulpes/container
```

## Default usage

```php
<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface UserDataAccessObjectInterface
{
    /**
     * @throws PDOException
     */
    public function fetchUser(string $email): null|object;

    /**
     * @throws PDOException
     */
    public function updateUserStatus(int $id, string $status): void;
}

readonly class UserDataAccessObject implements UserDataAccessObjectInterface
{
    public function __construct(private PDO $master, private PDO $replica)
    {
    }

    public function fetchUser(string $email): null|object
    {
        $statement = $this->replica->prepare("SELECT * FROM `users` WHERE email = :email;");
        $statement->bindValue(':email', $email, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetchObject() ?: null;
    }

    public function updateUserStatus(int $id, string $status): void
    {
        $statement = $this->master->prepare("UPDATE `users` SET `status`= :status WHERE id = :id;");
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->bindValue(':status', $status, PDO::PARAM_STR);
        $statement->execute();
    }
}

interface UserModelInterface
{

    /**
     * @throws Exception
     * @throws PDOException
     */
    public function fetchUser(null|string $email): object;

    /**
     * @throws Exception
     * @throws PDOException
     */
    public function updateUserStatus(null|string $email, string $status): void;
}

class UserModel implements UserModelInterface
{
    public function __construct(private UserDataAccessObjectInterface $dao)
    {
    }

    public function fetchUser(null|string $email): object
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email');
        }
        if (!($user = $this->dao->fetchUser($email))) {
            throw new Exception('User not found');
        }
        return $user;
    }

    public function updateUserStatus(null|string $email, string $status): void
    {
        if (!($user = $this->dao->fetchUser($email))) {
            throw new Exception('User not found');
        }
        $this->dao->updateUserStatus($user->id, $status);
    }
}

class FetchUserController implements RequestHandlerInterface
{
    public function __construct(
        private UserModelInterface       $userModel,
        private ResponseFactoryInterface $responseFactory,
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array)$request->getParsedBody();

        try {
            $user = $this->userModel->fetchUser($body['email'] ?? null);

            $response = $this->responseFactory->createResponse();
            $response->getBody()->write(json_encode($user));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (PDOException $exception) {

            $response = $this->responseFactory->createResponse(503);
            $response->getBody()->write(json_encode(['message' => $exception->getMessage()]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (Exception $exception) {

            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write(json_encode(['message' => $exception->getMessage()]));

            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}

class InactivateUserStatusController implements RequestHandlerInterface
{
    public function __construct(
        private UserModelInterface       $userModel,
        private ResponseFactoryInterface $responseFactory,
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array)$request->getParsedBody();

        try {
            $this->userModel->updateUserStatus($body['email'] ?? null, 'inactive');

            $response = $this->responseFactory->createResponse();
            $response->getBody()->write(json_encode([]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (PDOException $exception) {

            $response = $this->responseFactory->createResponse(503);
            $response->getBody()->write(json_encode(['message' => $exception->getMessage()]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (Exception $exception) {

            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write(json_encode(['message' => $exception->getMessage()]));

            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}

$container = new Container\Container([
    PDO::class => 'PDO::master',
    'PDO::master' => function () {
        return new PDO(getenv('PDO_MAS_DSN'), getenv('PDO_MAS_USR'), getenv('PDO_MAS_PWD'));
    },
    'PDO::replica' => function () {
        return new PDO(getenv('PDO_RPL_DSN'), getenv('PDO_RPL_USR'), getenv('PDO_RPL_PWD'));
    },
]);

$fethUserController = $container->get(FetchUserController::class);
$inactivateUserStatusController = $container->get(InactivateUserStatusController::class);

```
[PHP FIG PSR-20]: https://www.php-fig.org/psr/psr-11/
[Packagist]: http://packagist.org/packages/vulpes/container
[Composer]: http://getcomposer.org
