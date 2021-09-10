# PHP Runtimes

<img align="right" src="https://raw.githubusercontent.com/php-runtime/runtime/main/.github/logo.png">

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
we don't need too many different runtimes. This organization holds many PHP packages
with runtimes for the most popular environments. It is not "*the source of all
runtimes*", but rather a temporary place where runtimes can live before they move
in to Bref/Swoole/RoadRunner etc.

All runtimes have hard dependencies to make installation easier. Everything should
"just work".

Read more at the [Symfony documentation](https://symfony.com/doc/5.3/components/runtime.html).

## Available Runtimes

### Bref

Run your application on AWS Lambda with [Bref](https://bref.sh/).

* https://github.com/php-runtime/bref

### Google Cloud

Run your application on [Google Cloud](https://cloud.google.com/).

* https://github.com/php-runtime/google-cloud

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
* https://github.com/php-runtime/swoole-nyholm

### PHP-FPM and traditional web servers

These runtimes are for PHP-FPM and the more traditional web servers one might
use for local development.

#### Laravel

A runtime for [Laravel](https://laravel.com/) and Artisan.

* https://github.com/php-runtime/laravel

#### PSR-7 and PSR-15

Use the popular PSR architecture.

* https://github.com/php-runtime/psr-guzzle
* https://github.com/php-runtime/psr-laminas
* https://github.com/php-runtime/psr-nyholm
* https://github.com/php-runtime/psr-nyholm-laminas
* https://github.com/php-runtime/psr-17 (generic)

#### Symfony

The runtime for [Symfony](https://symfony.com/) is included in the runtime component.

* https://github.com/symfony/runtime

## Note about sessions

On Symfony < 5.4 session data will not be properly stored when using a "non-traditional"
webserver like Bref, Google, ReactPHP, RoadRunner or Swoole. This problem (or missing
feature) has been added in Symfony 5.4 and 6.0. You need to use the unreleased version
of `symfony/http-kernel` for sessions to work properly.

## Contribute

Contributions are always welcomed. Send your PR or open an issue here: https://github.com/php-runtime/runtime
