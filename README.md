# PHP FIG PSR-11 Container implementation.




```php
interface ControllerExampleInterface{}
interface FirstInterface{}
interface SecondInterface{}
interface MyClassInterface{}

class MyClass implements MyClassInterface, FirstInterface, SecondInterface
{
    public function __construct(string $name, array $options)
    {
        print sprintf("%s;%s\r\n", $name, http_build_query($options));
    }
}

class ControllerExample implements ControllerExampleInterface
{
    public function __construct(
        public MyClass          $obj0,
        public MyClassInterface $obj1,
        public FirstInterface   $obj2,
        public SecondInterface  $obj3,
        public MyClassInterface $obj4,
        public                  $obj5
    ){}
}

$container = new Container\Container(storage: [

    // You can set predefined constructor parameters or even a ready instance.
    MyClass::class => new MyClass('MyClass', ['port' => 3306]),

    // With the ::__construct suffix, the parameters will be available to other interfaces even after running MyClass
    MyClass::class . '::__construct' => ['name' => 'MyClass::__construct', 'options' => ['port' => 3307]],

    // An alias can also be set based on the interface, or based on your own keying.
    FirstInterface::class => MyClass::class,
    SecondInterface::class => function (Psr\Container\ContainerInterface $container) {
        return $container->get('my-own-id-2');
    },
    'my-own-id-1' => MyClass::class,
    'my-own-id-2' => MyClass::class,

    // If interface name match with classname (classname + "Interface"), you don't need to define
    // the method name (__construct) -> in the first build it will be overwritten with the instance
    MyClassInterface::class => ['name' => 'MyClassInterface', 'options' => function () {
        return ['port' => 3308];
    }],

    // If interface name not match with classname (classname + "Interface"),
    // you must define the method name (__construct)
    FirstInterface::class . '::__construct' => ['name' => 'FirstInterface', 'options' => ['port' => 3309]],
    'my-own-id-1::__construct' => ['name' => 'my-own-id-1', 'options' => ['port' => 3311]],
    'my-own-id-2::__construct' => ['name' => 'my-own-id-2'],
    ControllerExample::class . '::__construct' => ['obj5' => 'my-own-id-1'],
]);

$example = $container->get(ControllerExampleInterface::class);

//    MyClass;port=3306
//    MyClassInterface;port=3308
//    FirstInterface;port=3309
//    my-own-id-2;port=3307
//    my-own-id-1;port=3311

print_r($example);
//    ControllerExample Object
//        [obj0] => MyClass Object <MyClass>           MyClass;           port=3306
//        [obj1] => MyClass Object <MyClassInterface>  MyClassInterface;  port=3308
//        [obj2] => MyClass Object <FirstInterface>    FirstInterface;    port=3309
//        [obj3] => MyClass Object <my-own-id-2>       my-own-id-2;       port=3307
//        [obj4] => MyClass Object <MyClassInterface>  MyClassInterface;  port=3308
//        [obj5] => MyClass Object <my-own-id-1>       my-own-id-1;       port=3311
```