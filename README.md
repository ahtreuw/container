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

PDO:options:
    !php/const PDO::ATTR_ERRMODE: !php/const PDO::ERRMODE_EXCEPTION
    !php/const PDO::ATTR_DEFAULT_FETCH_MODE: !php/const PDO::FETCH_OBJ
    !php/const PDO::MYSQL_ATTR_INIT_COMMAND: 'SET NAMES utf8'
```

```php
/** not necessary, but can speed things up a lot for larger projects */ 
{
    /** If cached storage data available */
    {
        $storageData = [/** the cached storage data */];
    }

    /** If cached storage data NOT available */
    {
        $storageData = [/** the parsed data from Yaml */];
        $collector = new Container\StorageCollector($storageData);
        $collector->collect('MyNamespace', __DIR__ . '/my-directory');
        $storageData = $collector->getStorage();
        // save storage data to cache
    }
}

/** namespace MyNamespace */ 
{
    class ExampleDataAccessObject { public function __construct(PDO $master, PDO $replica) {} }

    interface ExampleModelInterface {}

    class ExampleModel implements ExampleModelInterface { public function __construct(ExampleDataAccessObject $dao) {} }

    class ExampleController { public function __construct(ExampleModelInterface $model) {} }
}

$container = new Container\Container($storageData);

$ctrl = $container->get(ExampleController::class);
```
[PHP FIG PSR-20]: https://www.php-fig.org/psr/psr-11/
[Packagist]: http://packagist.org/packages/vulpes/container
[Composer]: http://getcomposer.org
