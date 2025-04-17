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
interface ExampleDataAccessObjectInterface {}

class ExampleDataAccessObject implements UserDataAccessObjectInterface {
    public function __construct(PDO $master, PDO $replica) {}
}

class AnotherExampleDataAccessObject implements UserDataAccessObjectInterface {}

interface ExampleModelInterface {}

class ExampleModel implements UserModelInterface { 
    public function __construct(UserDataAccessObjectInterface $dao) {}
}

interface StorageCollectorWillKnowInterface {}
interface StorageCollectorWillKnowRequestInterface {}

class Request implements StorageCollectorWillKnowInterface, StorageCollectorWillKnowRequestInterface {
    public function __construct(ArrayObject $arrayObject = new ArrayObject) {}
}

class ExampleController {
    public function __construct(UserModelInterface $model, StorageCollectorWillKnowInterface $request) {
        // the (Request) $request object will be the same that under below
    }
}
```
[PHP FIG PSR-20]: https://www.php-fig.org/psr/psr-11/
[Packagist]: http://packagist.org/packages/vulpes/container
[Composer]: http://getcomposer.org
