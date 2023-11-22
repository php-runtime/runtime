# FrankenPHP Runtime for Symfony

A runtime for [FrankenPHP](https://frankenphp.dev/).

If you are new to the Symfony Runtime component, read more in the [main readme](https://github.com/php-runtime/runtime).

## Installation

```
composer require runtime/frankenphp-symfony
```

## Usage

Define the environment variable `APP_RUNTIME` for your application.

Dotenv Component is executed after Runtime Component, so APP_RUNTIME must be available in your container.

```
docker run \
    -e FRANKENPHP_CONFIG="worker ./public/index.php" \
    -e APP_RUNTIME=Runtime\\FrankenPhpSymfony\\Runtime \
    -v $PWD:/app \
    -p 80:80 -p 443:443 \
    dunglas/frankenphp
```

```php
// public/index.php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

## Options

* `frankenphp_loop_max`: the number of requests after which the worker must restart, to prevent weird memory leaks (default to `500`, set to `-1` to never restart)
