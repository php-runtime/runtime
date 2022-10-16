# FrankenPHP Runtime for Symfony

A runtime for [FrankenPHP](https://frankenphp.dev/).

If you are new to the Symfony Runtime component, read more in the [main readme](https://github.com/php-runtime/runtime).

## Installation

```
composer require runtime/frankenphp-symfony
```

## Usage

Define the environment variable `APP_RUNTIME` for your application.

```
// .env
APP_RUNTIME=Runtime\FrankenPhpSymfony\Runtime
```

```
// .rr.yaml
server:
    ...
    env:
        APP_RUNTIME: Runtime\FrankenPhpSymfony\Runtime
```

```php
// public/index.php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```
