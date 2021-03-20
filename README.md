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
namespace App;

class AcmeApplication
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
    return new App\AcmeApplication();
}
```

If you want to use this application in a CLI environment, you need a
`Runtime` that knows how to run an `App\AcmeApplication` object and print the output on
CLI. If you want to use it with Nginx/PHP-FPM then you need another `Runtime`
that converts the application output to a HTTP response.

## Why is this a good thing?

Since your application is not connected to the global state, it is very portable.
It is easy to create a `Runtime` to run the application with Bref, Swoole or
ReactPHP without making any change to the application itself.

Since most modern PHP application is based on Symfony's HttpKernel, PSR-7 or PSR-15
we dont need too many different runtimes. This organization holds many PHP packages
with runtimes for the most popular environments. It is not "the source of all
runtimes", but rather a temporary place where runtimes can live before they move
in to Bref/Swoole/RoadRunner etc.

All runtimes has hard dependencies to make installation easier. Everything should
"just work".
