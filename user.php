<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface UserDataAccessObjectInterface
{
    public function fetchUser(string $email): null|object;
}

readonly class UserDataAccessObject implements UserDataAccessObjectInterface
{
    public function __construct(private PDO $master, private PDO $replica)
    {
    }

    public function fetchUser(string $email): null|object
    {
        $statement = $this->replica->prepare("SELECT * FROM users WHERE email = :email");
        $statement->bindValue(':email', $email, PDO::PARAM_STR);
        $statement->execute();
        return $statement->fetchObject() ?: null;
    }
}

interface UserModelInterface
{
    public function login(null|string $email, null|string $password): void;
}

class UserModel implements UserModelInterface
{
    public function __construct(UserDataAccessObjectInterface $dao)
    {
    }

    public function login(null|string $email, null|string $password): void
    {

    }
}

class ExampleController implements RequestHandlerInterface
{
    public function __construct(
        private UserModelInterface       $userModel,
        private ResponseFactoryInterface $responseFactory,
    )
    {
        // the (Request) $request object will be the same that under below
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array)$request->getParsedBody();

        try {
            $this->userModel->login(
                $body['email'] ?? null,
                $body['password'] ?? null
            );
        }catch (Exception $exception) {
            $response = $this->responseFactory->createResponse(400);

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
var_dump(PDO::class);die;