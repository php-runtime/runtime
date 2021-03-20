# PHP Runtimes

In early 2021, Symfony created a "Runtime component". This component may look
complex, weird and full of hacks but it is a **game changer** for how we run PHP
applications.

With the Runtime component, we can look at each application as a "black box". A box
that has no connection to globals like `$_SERVER` or `$_GET`. To run the application
you need (you guessed it) a `Runtime`. It is a class that looks at the black box
to figure out what input it requires and handles the output.

Consider this small application that returns an UUID.

```php
namespace Acme;

class Application
{
    public function run() {
        return Uuid::uuid4()->toString();
    }
}
```

To use this application with the Runtime component. We need our front-controller
to return a callable that will create the application.

```php
// index.php

return function() {
    return new Acme\Application();
}
```

If you want to use this application in a CLI environment, you need a
`Runtime` that knows how to run an `Acme\Application` object and print the output on
CLI. If you want to use it with Nginx/PHP-FPM then you need another `Runtime`
that converts the application's output to a HTTP response.

## Why is this a good thing?

Since your application is not connected to the global state, it is very portable.
It is easy to create a `Runtime` to run the application with Bref, Swoole or
ReactPHP without making any change to the application itself.

Since most modern PHP application is based on Symfony's HttpKernel, PSR-7 or PSR-15
we dont need too many different runtimes. This organization holds many PHP packages
with runtimes for the most popular environments. It is not "*the source of all
runtimes*", but rather a temporary place where runtimes can live before they move
in to Bref/Swoole/RoadRunner etc.

All runtimes has hard dependencies to make installation easier. Everything should
"just work".

## Supported environments

### Bref

Run your application on AWS Lambda with [Bref](https://bref.sh/).

* https://github.com/php-runtime/bref

### PSR-7 and PSR-15

Use the popular PSR architecture.

* https://github.com/php-runtime/psr-nyholm-laminas

### ReactPHP

Event-driven, non-blocking I/O with [ReactPHP](https://reactphp.org/).

* https://github.com/php-runtime/reactphp

### RoadRunner

Spin up multiple PHP processes with Golang using [RoadRunner](https://roadrunner.dev/).

* https://github.com/php-runtime/roadrunner-nyholm
* https://github.com/php-runtime/roadrunner-symfony-nyholm

### Swoole

Build high-performance, scalable, concurrent HTTP services with [Swoole](https://www.swoole.co.uk/).

* https://github.com/php-runtime/swoole

## Contribute

Contributions are always welcomed. Send you PR or open an issue here: https://github.com/php-runtime/runtime
