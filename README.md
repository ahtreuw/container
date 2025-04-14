# A dependency injection (DI) container

This repository contains the [PHP FIG PSR-11] Container implementation.

## Install

Via Composer Package is available on [Packagist], You can install it using [Composer].

``` bash
$ composer require vulpes/container
```

## Default usage

```php
interface ExampleDataAccessObjectInterface {}

class ExampleDataAccessObject implements ExampleDataAccessObjectInterface {
    public function __construct(PDO $master, PDO $replica) {}
}

class AnotherExampleDataAccessObject implements ExampleDataAccessObjectInterface {}

interface ExampleModelInterface {}

class ExampleModel implements ExampleModelInterface { 
    public function __construct(ExampleDataAccessObjectInterface $dao) {}
}

interface StorageCollectorWillKnowInterface {}
interface StorageCollectorWillKnowRequestInterface {}

class Request implements StorageCollectorWillKnowInterface, StorageCollectorWillKnowRequestInterface {
    public function __construct(ArrayObject $arrayObject = new ArrayObject) {}
}

class ExampleController {
    public function __construct(ExampleModelInterface $model, StorageCollectorWillKnowInterface $request) {
        // the (Request) $request object will be the same that under below
    }
    
    public function handle(StorageCollectorWillKnowRequestInterface $request, int $id, string $userName) {
        // the (Request) $request object will be the same as above
    }
}
```
[PHP FIG PSR-20]: https://www.php-fig.org/psr/psr-11/
[Packagist]: http://packagist.org/packages/vulpes/container
[Composer]: http://getcomposer.org
