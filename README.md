# A dependency injection (DI) container

This repository contains the [PHP FIG PSR-11] Container implementation.

## Install

Via Composer Package is available on [Packagist], You can install it using [Composer].

``` bash
$ composer require vulpes/container
```

## Default usage

```php
// namespace MyNamespace
{
    interface ExampleDataAccessObjectInterface {}
    
    class ExampleDataAccessObject implements ExampleDataAccessObjectInterface {
        public function __construct(PDO $master, PDO $replica, string $key) {}
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
}

// bootstrap
{
    // If cached storage data available
    {
        // not necessary, but can speed things up a lot for larger projects
        $storageData = [/** the cached storage data */];
    }

    // If cached storage data NOT available
    {
        $storageData = [/** the parsed data from Yaml */];
        
        $collector = new Container\StorageCollector($storageData);
        
        // You can collect interfaces and constructor/method
        // parameters for faster processes or easier configuration
        // it will not overwrite existing settings (like from Yaml)
        $collector->collect('MyNamespace', __DIR__ . '/src/my-directory');
        
        // returns the existing and new settings together
        $storageData = $collector->getStorage();
        
        // save storage data to cache..
    }
    
    // create container instance with predefined storage values
    $container = new Container\Container($storageData);
    
    // to process the parsed data from Yaml
    $container->add(new Container\Processor\YamlProcessor);
     
    // create instance of ExampleController
    $exampleController = $container->get(ExampleController::class);
    
    // set AnotherExampleDataAccessObject to use when ExampleDataAccessObjectInterface build
    $container->set(ExampleDataAccessObjectInterface::class, AnotherExampleDataAccessObject::class);
     
    // create instance of ExampleController with AnotherExampleDataAccessObject
    $exampleController = $container->get(ExampleController::class);
    
    // to process \Closure objects
    $container->add(new Container\Processor\ClosureProcessor);
    
    // set Closure to use when ExampleDataAccessObjectInterface build
    $container->set(ExampleDataAccessObjectInterface::class, 
        function(Container\ContainerInterface $container, string $id, mixed ...$args){
            return new class implements ExampleDataAccessObjectInterface {};
        });
     
    // create instance of ExampleController with a custom ExampleDataAccessObjectInterface
    $exampleController = $container->get(ExampleController::class);
     
    // create instance of ExampleController and call handle function with Request, with custom builtin parameters
    $handleResult = $container->get(ExampleController::class . '::handle', '17', 'user-name');
     
    // call ExampleController handle method with Request, with custom builtin parameters
    $handleResult = $container->call($exampleController, 'handle', '17', 'user-name');
}
```

#### The Yaml config for the example above

```yaml
ExampleDataAccessObject:
  params:
    - obj: PDO:master
    - obj: PDO:replica
    - val: example-key

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

## Customizable usage

```php
$container = new Container\Container(

    // storage: You can define in advance what values You want to use with which key/interface
    storage: [], // storage
    
    // factory: You can define how to instantiate classes or call functions or get the required Reflector classes
    factory: new Container\Factory, // Container\FactoryInterface

    // parameters: You can customize how to build the necessary parameter values for different methods/constructors
    parameters: new Container\Parameters, // Container\ParametersInterface

    // handler: You can customize how to build the necessary parameter values for different methods/constructors
    handler: new Container\ProcessHandler // Container\ProcessHandlerInterface
);

// With the help of YamlProcessor, You can pre-define the values of
// various parameters and separate classes according to the way of use.
$container->add(processor: new Container\Processor\YamlProcessor);

// With the help of ClosureProcessor, You can use \Closure objects to build your own objects
$container->add(processor: new Container\Processor\ClosureProcessor);

// Or you can create your own processor if needed
$container->add(processor: new MyOwnProcessor); // implements Container\ProcessorInterface
```
[PHP FIG PSR-20]: https://www.php-fig.org/psr/psr-11/
[Packagist]: http://packagist.org/packages/vulpes/container
[Composer]: http://getcomposer.org
