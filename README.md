# A dependency injection (DI) container
This repository contains the [PHP FIG PSR-11] Container implementation.

## Install
Via Composer
Package is available on [Packagist], you can install it using [Composer].
``` bash
$ composer require vulpes/container
```

## Usage
```yaml
conf:
  ExampleDataAccessObject:
    params:
      - obj: PDO:master
      - obj: PDO:replica
      - val: random value
  PDO:master:
    class: PDO
    params:
      - env: MYSQL01_DSN
      - env: MYSQL01_USER
      - env: MYSQL01_PASS
      - arg: PDO:options
  PDO:replica:
    class: PDO
    params:
      - env: MYSQL02_DSN
      - env: MYSQL02_USER
      - env: MYSQL02_PASS
      - arg: PDO:options

args:
  PDO:options:
    !php/const PDO::ATTR_ERRMODE: !php/const PDO::ERRMODE_EXCEPTION
    !php/const PDO::ATTR_DEFAULT_FETCH_MODE: !php/const PDO::FETCH_OBJ
    !php/const PDO::MYSQL_ATTR_INIT_COMMAND: 'SET NAMES utf8'
```
```php
use Symfony\Component\Yaml\Parser;
use Vulpes\Container\Container;
use Vulpes\Container\Factory;
use Vulpes\Container\Parser\SymfonyParser;
use Vulpes\Container\Storage;

class ExampleDataAccessObject
{
    public function __construct(PDO $master, PDO $replica) {}
}

interface ExampleModelInterface {}

class ExampleModel implements ExampleModelInterface
{
    public function __construct(ExampleDataAccessObject $dao) {}
}

class ExampleController
{
    public function __construct(ExampleModelInterface $model) {}
}

$factory = new Factory;
$storage = new Storage(new SymfonyParser(new Parser));
$storage->readConfigFile(__DIR__ . '/example.yaml');

$container = new Container($factory, $storage);

$ctrl = $container->get(ExampleController::class);
```
[PHP FIG PSR-20]: https://www.php-fig.org/psr/psr-11/
[Packagist]: http://packagist.org/packages/vulpes/container
[Composer]: http://getcomposer.org
